<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PesertaKelasRequest;
use App\Models\KelasKuliah;
use App\Models\PesertaKelas;
use Illuminate\Http\RedirectResponse;

/**
 * Peserta kelas: menambah satu mahasiswa hasil pencarian, atau mengeluarkannya.
 */
class PesertaKelasController extends Controller
{
    public function store(PesertaKelasRequest $request, KelasKuliah $kelasKuliah): RedirectResponse
    {
        $kelasKuliah->mahasiswa()->attach($request->validated('mahasiswa_id'));

        return redirect()->route('admin.kelas-kuliah.show', $kelasKuliah)->with('sukses', 'Peserta berhasil ditambahkan.');
    }

    public function destroy(KelasKuliah $kelasKuliah, PesertaKelas $peserta): RedirectResponse
    {
        abort_if($peserta->kelas_kuliah_id !== $kelasKuliah->kelas_kuliah_id, 404);

        if ($pesan = $peserta->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        $peserta->delete();

        return redirect()->route('admin.kelas-kuliah.show', $kelasKuliah)->with('sukses', 'Peserta berhasil dikeluarkan dari kelas.');
    }
}
