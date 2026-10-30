<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\CapaianMahasiswa;
use App\Services\CapaianService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Capaian Pembelajaran Mahasiswa (bagian 7.6.10 spesifikasi, KF-15). Read-only dan hanya data
 * milik mahasiswa yang sedang login: alamatnya tidak memuat id mahasiswa, jadi data mahasiswa
 * lain tidak bisa dibuka lewat alamat (bagian 6).
 */
class CapaianMahasiswaController extends Controller
{
    public function __invoke(Request $request, CapaianMahasiswa $capaianMahasiswa, CapaianService $capaian): View
    {
        $mahasiswa = $request->user()->mahasiswa;
        abort_if($mahasiswa === null, 403);

        return view('mahasiswa.capaian', [
            'mahasiswa' => $mahasiswa,
            'ambang' => $capaian->ambang(),
            ...$capaianMahasiswa->susun($mahasiswa, $request->query('periode'), $request->query('mata_kuliah')),
        ]);
    }
}
