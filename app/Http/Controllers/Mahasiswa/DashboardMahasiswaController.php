<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Services\CapaianService;
use App\Services\DashboardMahasiswa;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard Mahasiswa (KD-74): ringkasan dan grafik capaian CPL kumulatif. Sama seperti halaman
 * Capaian Pembelajaran, alamatnya tidak memuat id mahasiswa, jadi yang tampil selalu data milik
 * mahasiswa yang sedang login (bagian 6).
 */
class DashboardMahasiswaController extends Controller
{
    public function __invoke(Request $request, DashboardMahasiswa $dashboard, CapaianService $capaian): View
    {
        $mahasiswa = $request->user()->mahasiswa;
        abort_if($mahasiswa === null, 403);

        return view('mahasiswa.dashboard', [
            'mahasiswa' => $mahasiswa,
            'ambang' => $capaian->ambang(),
            ...$dashboard->susun($mahasiswa, $request->query('kurikulum')),
        ]);
    }
}
