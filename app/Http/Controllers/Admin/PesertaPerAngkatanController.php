<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PesertaPerAngkatanRequest;
use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

/**
 * Menambah semua mahasiswa satu angkatan ke kelas sekaligus (bagian 7.6.6 spesifikasi).
 */
class PesertaPerAngkatanController extends Controller
{
    public function store(PesertaPerAngkatanRequest $request, KelasKuliah $kelasKuliah): RedirectResponse
    {
        $angkatan = $request->integer('angkatan');
        $daftarMahasiswa = Mahasiswa::where('angkatan', $angkatan)->get();

        // Lewati mahasiswa yang sudah mengikuti MK ini pada periode yang sama, di kelas ini
        // maupun kelas lain (KD-37).
        $belumTerdaftar = Mahasiswa::where('angkatan', $angkatan)
            ->whereDoesntHave('kelasKuliah', fn (Builder $query) => $query
                ->where('mata_kuliah_id', $kelasKuliah->mata_kuliah_id)
                ->where('periode_akademik_id', $kelasKuliah->periode_akademik_id))
            ->pluck('mahasiswa_id');

        $kelasKuliah->mahasiswa()->attach($belumTerdaftar);

        $pesan = "{$belumTerdaftar->count()} mahasiswa angkatan {$angkatan} ditambahkan.";
        $dilewati = $daftarMahasiswa->count() - $belumTerdaftar->count();

        if ($dilewati > 0) {
            $pesan .= " {$dilewati} mahasiswa dilewati karena sudah terdaftar di kelas mata kuliah ini.";
        }

        return redirect()->route('admin.kelas-kuliah.show', $kelasKuliah)->with('sukses', $pesan);
    }
}
