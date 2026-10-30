<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DosenPengampuRequest;
use App\Models\Dosen;
use App\Models\KelasKuliah;
use Illuminate\Http\RedirectResponse;

/**
 * Dosen pengampu kelas; satu kelas boleh diampu lebih dari satu dosen (bagian 7.6.6).
 */
class DosenPengampuController extends Controller
{
    public function store(DosenPengampuRequest $request, KelasKuliah $kelasKuliah): RedirectResponse
    {
        $kelasKuliah->dosen()->attach($request->validated('dosen_id'));

        return redirect()->route('admin.kelas-kuliah.show', $kelasKuliah)->with('sukses', 'Dosen pengampu berhasil ditambahkan.');
    }

    public function destroy(KelasKuliah $kelasKuliah, Dosen $dosen): RedirectResponse
    {
        $kelasKuliah->dosen()->detach($dosen);

        return redirect()->route('admin.kelas-kuliah.show', $kelasKuliah)->with('sukses', "{$dosen->nama} tidak lagi mengampu kelas ini.");
    }
}
