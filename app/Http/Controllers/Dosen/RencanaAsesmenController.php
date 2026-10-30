<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dosen\RencanaAsesmenRequest;
use App\Models\KelasKuliah;
use App\Models\MetodePenilaian;
use App\Models\RencanaAsesmen;
use App\Services\MatriksBobotKelas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Kelola Rencana Asesmen satu kelas beserta matriks bobotnya (bagian 7.6.7 spesifikasi).
 * Hanya dosen pengampu kelas itu yang boleh membuka halaman ini (KelasKuliahPolicy).
 */
class RencanaAsesmenController extends Controller
{
    public function index(KelasKuliah $kelasKuliah): View
    {
        Gate::authorize('kelola', $kelasKuliah);

        $kelasKuliah->load(['mataKuliah.kurikulum', 'periodeAkademik']);

        return view('dosen.rencana-asesmen.index', [
            'kelas' => $kelasKuliah,
            'matriks' => new MatriksBobotKelas($kelasKuliah),
        ]);
    }

    public function create(KelasKuliah $kelasKuliah): View|RedirectResponse
    {
        Gate::authorize('kelola', $kelasKuliah);

        if ($kelasKuliah->daftarSubCpmk()->isEmpty()) {
            return redirect()
                ->route('dosen.kelas.rencana-asesmen.index', $kelasKuliah)
                ->with('gagal', 'Mata kuliah ini belum punya Sub-CPMK. Hubungi Admin untuk melengkapi data kurikulum.');
        }

        return $this->form($kelasKuliah, new RencanaAsesmen);
    }

    public function store(RencanaAsesmenRequest $request, KelasKuliah $kelasKuliah): RedirectResponse
    {
        DB::transaction(function () use ($request, $kelasKuliah) {
            $rencanaAsesmen = $kelasKuliah->rencanaAsesmen()->create($request->safe()->except('kontribusi'));
            $rencanaAsesmen->petakanSubCpmk($request->validated('kontribusi'));
        });

        return redirect()->route('dosen.kelas.rencana-asesmen.index', $kelasKuliah)->with('sukses', 'Rencana asesmen berhasil ditambahkan.');
    }

    public function edit(KelasKuliah $kelasKuliah, RencanaAsesmen $rencanaAsesmen): View
    {
        Gate::authorize('kelola', $kelasKuliah);
        $this->pastikanMilikKelas($kelasKuliah, $rencanaAsesmen);

        return $this->form($kelasKuliah, $rencanaAsesmen->load('subCpmk'));
    }

    public function update(RencanaAsesmenRequest $request, KelasKuliah $kelasKuliah, RencanaAsesmen $rencanaAsesmen): RedirectResponse
    {
        $this->pastikanMilikKelas($kelasKuliah, $rencanaAsesmen);

        DB::transaction(function () use ($request, $rencanaAsesmen) {
            $rencanaAsesmen->update($request->safe()->except('kontribusi'));
            $rencanaAsesmen->petakanSubCpmk($request->validated('kontribusi'));
        });

        return redirect()->route('dosen.kelas.rencana-asesmen.index', $kelasKuliah)->with('sukses', 'Rencana asesmen berhasil diperbarui.');
    }

    public function destroy(KelasKuliah $kelasKuliah, RencanaAsesmen $rencanaAsesmen): RedirectResponse
    {
        Gate::authorize('kelola', $kelasKuliah);
        $this->pastikanMilikKelas($kelasKuliah, $rencanaAsesmen);

        if ($pesan = $rencanaAsesmen->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        // Pemetaannya ke Sub-CPMK ikut terhapus (cascade, KD-20).
        $rencanaAsesmen->delete();

        return redirect()->route('dosen.kelas.rencana-asesmen.index', $kelasKuliah)->with('sukses', 'Rencana asesmen berhasil dihapus.');
    }

    private function form(KelasKuliah $kelas, RencanaAsesmen $rencanaAsesmen): View
    {
        $kelas->load(['mataKuliah', 'periodeAkademik']);

        return view('dosen.rencana-asesmen.form', [
            'kelas' => $kelas,
            'rencanaAsesmen' => $rencanaAsesmen,
            'daftarMetode' => MetodePenilaian::orderBy('kode_metode')->pluck('nama_metode', 'metode_penilaian_id'),
            'daftarSubCpmk' => $kelas->daftarSubCpmk(),
            // Bobot kontribusi tersimpan: id Sub-CPMK => bobot.
            'kontribusiTersimpan' => $rencanaAsesmen->exists
                ? $rencanaAsesmen->subCpmk->mapWithKeys(fn ($subCpmk) => [$subCpmk->sub_cpmk_id => $subCpmk->pivot->bobot_kontribusi])->all()
                : [],
        ]);
    }

    /**
     * Asesmen di alamat harus milik kelas di alamat yang sama.
     */
    private function pastikanMilikKelas(KelasKuliah $kelas, RencanaAsesmen $rencanaAsesmen): void
    {
        abort_if($rencanaAsesmen->kelas_kuliah_id !== $kelas->kelas_kuliah_id, 404);
    }
}
