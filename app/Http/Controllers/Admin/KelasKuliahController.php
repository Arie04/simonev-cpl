<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KelasKuliahRequest;
use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeAkademik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Kelas kuliah per periode, beserta dosen pengampu dan peserta (bagian 7.6.6 spesifikasi).
 */
class KelasKuliahController extends Controller
{
    public function index(Request $request): View
    {
        $periode = $this->periodeTerpilih($request);

        return view('admin.kelas-kuliah.index', [
            'periode' => $periode,
            'daftarPilihanPeriode' => $this->pilihanPeriode(),
            // Join dipakai untuk mengurutkan berdasarkan kode mata kuliah.
            'daftarKelas' => KelasKuliah::select('kelas_kuliah.*')
                ->join('mata_kuliah', 'mata_kuliah.mata_kuliah_id', '=', 'kelas_kuliah.mata_kuliah_id')
                ->with(['mataKuliah.kurikulum', 'dosen'])
                ->withCount('pesertaKelas')
                ->where('kelas_kuliah.periode_akademik_id', $periode?->periode_akademik_id)
                ->cari($this->kataPencarian($request))
                ->orderBy('mata_kuliah.kode_mata_kuliah')
                ->orderBy('kelas_kuliah.nama_kelas')
                ->paginate($this->jumlahPerHalaman($request))
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $periode = $this->periodeTerpilih($request);

        if ($periode === null) {
            return redirect()->route('admin.periode-akademik.create')->with('gagal', 'Tambahkan periode akademik terlebih dahulu.');
        }

        return $this->form(new KelasKuliah(['periode_akademik_id' => $periode->periode_akademik_id]));
    }

    public function store(KelasKuliahRequest $request): RedirectResponse
    {
        $kelas = KelasKuliah::create($request->validated());

        // Setelah kelas dibuat, Admin langsung diarahkan ke detail untuk mengatur dosen dan peserta.
        return redirect()->route('admin.kelas-kuliah.show', $kelas)->with('sukses', 'Kelas berhasil ditambahkan. Atur dosen pengampu dan pesertanya.');
    }

    /**
     * Detail kelas: dosen pengampu dan peserta. Pencarian mahasiswa memakai ?cari_mahasiswa=.
     */
    public function show(Request $request, KelasKuliah $kelasKuliah): View
    {
        $kelasKuliah->load(['mataKuliah.kurikulum', 'periodeAkademik', 'dosen', 'pesertaKelas.mahasiswa']);
        $kataKunci = $request->query('cari_mahasiswa');

        return view('admin.kelas-kuliah.show', [
            'kelas' => $kelasKuliah,
            'daftarPeserta' => $kelasKuliah->pesertaKelas->sortBy('mahasiswa.nim'),
            'pilihanDosen' => Dosen::whereNotIn('dosen_id', $kelasKuliah->dosen->modelKeys())
                ->orderBy('nama')
                ->get()
                ->mapWithKeys(fn (Dosen $dosen) => [$dosen->dosen_id => "{$dosen->nama} ({$dosen->nidn})"]),
            'pilihanAngkatan' => Mahasiswa::distinct()->orderByDesc('angkatan')->pluck('angkatan', 'angkatan'),
            'hasilCari' => is_string($kataKunci) && $kataKunci !== ''
                ? $this->cariMahasiswaBelumTerdaftar($kelasKuliah, $kataKunci)
                : null,
        ]);
    }

    public function edit(KelasKuliah $kelasKuliah): View
    {
        return $this->form($kelasKuliah);
    }

    public function update(KelasKuliahRequest $request, KelasKuliah $kelasKuliah): RedirectResponse
    {
        $kelasKuliah->update($request->validated());

        return redirect()->route('admin.kelas-kuliah.show', $kelasKuliah)->with('sukses', 'Kelas berhasil diperbarui.');
    }

    public function destroy(KelasKuliah $kelasKuliah): RedirectResponse
    {
        if ($pesan = $kelasKuliah->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        // Dosen pengampu dan peserta kelas ini ikut terhapus (cascade, KD-20).
        $kelasKuliah->delete();

        return redirect()->route('admin.kelas-kuliah.index')->with('sukses', 'Kelas berhasil dihapus.');
    }

    private function form(KelasKuliah $kelas): View
    {
        return view('admin.kelas-kuliah.form', [
            'kelas' => $kelas,
            'daftarPilihanPeriode' => $this->pilihanPeriode(),
            // MK dari semua kurikulum, karena kelas kedua kurikulum bisa berjalan bersamaan (bagian 1).
            'daftarPilihanMataKuliah' => MataKuliah::with('kurikulum')
                ->get()
                ->sortBy([['kurikulum.tahun_berlaku', 'desc'], ['kode_mata_kuliah', 'asc']])
                ->mapWithKeys(fn (MataKuliah $mataKuliah) => [
                    $mataKuliah->mata_kuliah_id => "{$mataKuliah->kode_mata_kuliah} {$mataKuliah->nama_mata_kuliah} ({$mataKuliah->kurikulum->nama_kurikulum})",
                ]),
        ]);
    }

    /**
     * @return Collection<string, string> id periode => "2026/2027 Ganjil", dari yang terbaru
     */
    private function pilihanPeriode(): Collection
    {
        return PeriodeAkademik::urutTerbaru()->get()->mapWithKeys(fn (PeriodeAkademik $periode) => [$periode->periode_akademik_id => $periode->nama()]);
    }

    /**
     * Mahasiswa yang cocok dengan kata kunci dan belum mengikuti MK ini pada periode kelas ini.
     */
    private function cariMahasiswaBelumTerdaftar(KelasKuliah $kelas, string $kataKunci): Collection
    {
        return Mahasiswa::cari($kataKunci)
            ->whereDoesntHave('kelasKuliah', fn (Builder $query) => $query
                ->where('mata_kuliah_id', $kelas->mata_kuliah_id)
                ->where('periode_akademik_id', $kelas->periode_akademik_id))
            ->orderBy('nim')
            ->limit(20)
            ->get();
    }
}
