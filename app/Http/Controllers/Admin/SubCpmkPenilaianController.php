<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubCpmkPenilaianRequest;
use App\Models\MetodePenilaian;
use App\Models\SubCpmk;
use App\Models\SubCpmkPenilaian;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Rincian penilaian Sub-CPMK, dikelola di halaman detail Sub-CPMK (bagian 7.6.4 spesifikasi).
 */
class SubCpmkPenilaianController extends Controller
{
    public function store(SubCpmkPenilaianRequest $request, SubCpmk $subCpmk): RedirectResponse
    {
        $subCpmk->penilaian()->create($request->validated());

        return redirect()->route('admin.sub-cpmk.show', $subCpmk)->with('sukses', 'Rincian penilaian berhasil ditambahkan.');
    }

    public function edit(SubCpmk $subCpmk, SubCpmkPenilaian $penilaian): View
    {
        $this->pastikanMilikSubCpmk($subCpmk, $penilaian);

        return view('admin.sub-cpmk.penilaian', [
            'subCpmk' => $subCpmk,
            'penilaian' => $penilaian,
            'daftarMetode' => MetodePenilaian::orderBy('kode_metode')->pluck('nama_metode', 'metode_penilaian_id'),
        ]);
    }

    public function update(SubCpmkPenilaianRequest $request, SubCpmk $subCpmk, SubCpmkPenilaian $penilaian): RedirectResponse
    {
        $this->pastikanMilikSubCpmk($subCpmk, $penilaian);

        $penilaian->update($request->validated());

        return redirect()->route('admin.sub-cpmk.show', $subCpmk)->with('sukses', 'Rincian penilaian berhasil diperbarui.');
    }

    public function destroy(SubCpmk $subCpmk, SubCpmkPenilaian $penilaian): RedirectResponse
    {
        $this->pastikanMilikSubCpmk($subCpmk, $penilaian);

        $penilaian->delete();

        return redirect()->route('admin.sub-cpmk.show', $subCpmk)->with('sukses', 'Rincian penilaian berhasil dihapus.');
    }

    /**
     * Rincian penilaian di alamat harus milik Sub-CPMK di alamat yang sama.
     */
    private function pastikanMilikSubCpmk(SubCpmk $subCpmk, SubCpmkPenilaian $penilaian): void
    {
        abort_if($penilaian->sub_cpmk_id !== $subCpmk->sub_cpmk_id, 404);
    }
}
