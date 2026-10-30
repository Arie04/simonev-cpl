<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dosen\NilaiRequest;
use App\Models\KelasKuliah;
use App\Models\RencanaAsesmen;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Input Nilai Asesmen (bagian 7.6.8 spesifikasi, KF-10). Filter bertingkat
 * Mata Kuliah → Periode → Kelas → Rencana Asesmen hanya berisi kelas yang diampu dosen.
 */
class InputNilaiController extends Controller
{
    public function edit(Request $request): View
    {
        $filter = $this->filter($request);
        $asesmen = $filter['asesmen'];

        return view('dosen.nilai.edit', [
            ...$filter,
            'daftarPeserta' => $asesmen?->kelasKuliah->pesertaKelas()->with('mahasiswa')->get()->sortBy('mahasiswa.nim')->values(),
            // Nilai tersimpan: id peserta_kelas => nilai.
            'nilaiTersimpan' => $asesmen?->nilaiAsesmen()->pluck('nilai', 'peserta_kelas_id') ?? collect(),
        ]);
    }

    public function update(NilaiRequest $request, RencanaAsesmen $rencanaAsesmen): RedirectResponse
    {
        $hasil = $rencanaAsesmen->simpanNilai($request->validated('nilai'));

        return redirect()
            ->route('dosen.nilai.edit', ['kelas' => $rencanaAsesmen->kelas_kuliah_id, 'asesmen' => $rencanaAsesmen->rencana_asesmen_id])
            ->with('sukses', "Nilai {$rencanaAsesmen->nama_asesmen} berhasil disimpan: {$hasil['terisi']} terisi, {$hasil['kosong']} belum diinput.");
    }

    /**
     * Pilihan dan isian filter bertingkat. Pilihan yang tidak cocok dengan tingkat di atasnya
     * dikosongkan, dan tingkat yang hanya punya satu pilihan dipilih otomatis. Jika ?kelas=
     * diisi (mis. dari Dashboard), mata kuliah dan periodenya mengikuti kelas itu.
     *
     * @return array<string, mixed>
     */
    private function filter(Request $request): array
    {
        $daftarKelasDiampu = KelasKuliah::with(['mataKuliah', 'periodeAkademik'])
            ->whereHas('dosen', fn (Builder $query) => $query->whereKey($request->user()->dosen?->dosen_id))
            ->get();

        // Tautan langsung (mis. dari Dashboard) cukup membawa ?kelas=. Jika form filter dikirim,
        // ?mata_kuliah= ikut terisi dan pilihan pengguna yang dipakai.
        $kelasDariAlamat = $request->missing('mata_kuliah')
            ? $daftarKelasDiampu->firstWhere('kelas_kuliah_id', $request->query('kelas'))
            : null;

        $pilihanMataKuliah = $daftarKelasDiampu->pluck('mataKuliah')->unique('mata_kuliah_id')->sortBy('kode_mata_kuliah');
        $mataKuliahId = $this->pilihDari($pilihanMataKuliah->pluck('mata_kuliah_id')->values()->all(), $kelasDariAlamat?->mata_kuliah_id ?? $request->query('mata_kuliah'));

        $kelasMataKuliah = $daftarKelasDiampu->where('mata_kuliah_id', $mataKuliahId);
        $pilihanPeriode = $kelasMataKuliah->pluck('periodeAkademik')->unique('periode_akademik_id')
            ->sortBy([['tahun_akademik', 'desc'], ['semester_periode', 'desc']]);
        $periodeId = $this->pilihDari($pilihanPeriode->pluck('periode_akademik_id')->values()->all(), $kelasDariAlamat?->periode_akademik_id ?? $request->query('periode'));

        $pilihanKelas = $kelasMataKuliah->where('periode_akademik_id', $periodeId)->sortBy('nama_kelas');
        $kelasId = $this->pilihDari($pilihanKelas->pluck('kelas_kuliah_id')->values()->all(), $request->query('kelas'));
        $kelas = $pilihanKelas->firstWhere('kelas_kuliah_id', $kelasId);

        $pilihanAsesmen = $kelas?->rencanaAsesmen()->with('kelasKuliah')->orderBy('tanggal_asesmen')->orderBy('created_at')->get() ?? collect();
        $asesmenId = $this->pilihDari($pilihanAsesmen->pluck('rencana_asesmen_id')->values()->all(), $request->query('asesmen'));

        return [
            'pilihanMataKuliah' => $pilihanMataKuliah->mapWithKeys(fn ($mataKuliah) => [$mataKuliah->mata_kuliah_id => "{$mataKuliah->kode_mata_kuliah} {$mataKuliah->nama_mata_kuliah}"]),
            'pilihanPeriode' => $pilihanPeriode->mapWithKeys(fn ($periode) => [$periode->periode_akademik_id => $periode->nama()]),
            'pilihanKelas' => $pilihanKelas->mapWithKeys(fn ($kelasKuliah) => [$kelasKuliah->kelas_kuliah_id => 'Kelas '.$kelasKuliah->nama_kelas]),
            'pilihanAsesmen' => $pilihanAsesmen->mapWithKeys(fn ($asesmen) => [$asesmen->rencana_asesmen_id => $asesmen->nama_asesmen]),
            'mataKuliahId' => $mataKuliahId,
            'periodeId' => $periodeId,
            'kelas' => $kelas,
            'asesmen' => $pilihanAsesmen->firstWhere('rencana_asesmen_id', $asesmenId),
            'adaKelasDiampu' => $daftarKelasDiampu->isNotEmpty(),
        ];
    }

    /**
     * Nilai terpilih jika ada di daftar pilihan; jika pilihannya hanya satu, itulah yang dipilih.
     *
     * @param  list<string>  $daftarId
     */
    private function pilihDari(array $daftarId, mixed $dipilih): ?string
    {
        if (is_string($dipilih) && in_array($dipilih, $daftarId, true)) {
            return $dipilih;
        }

        return count($daftarId) === 1 ? $daftarId[0] : null;
    }
}
