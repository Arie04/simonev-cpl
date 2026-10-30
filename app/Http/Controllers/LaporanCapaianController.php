<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\CapaianService;
use App\Services\FilterLaporan;
use App\Services\LaporanCapaian;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Laporan Capaian Pembelajaran (bagian 7.6.9 spesifikasi, KF-13, KF-14, KF-16). Halaman yang
 * sama dipakai Koprodi (seluruh prodi) dan Dosen (hanya kelas yang ia ampu, KD-46).
 */
class LaporanCapaianController extends Controller
{
    public function __invoke(Request $request, LaporanCapaian $laporan, CapaianService $capaian): View
    {
        $dosen = null;

        if ($request->session()->get('peran_aktif') === Role::DOSEN) {
            $dosen = $request->user()->dosen;
            // Akun berperan Dosen tanpa data dosen tidak boleh melihat laporan seluruh prodi.
            abort_if($dosen === null, 403);
        }

        $filter = new FilterLaporan($request, $dosen);
        $tab = array_key_exists((string) $request->query('tab'), LaporanCapaian::TAB) ? $request->query('tab') : 'cpl';

        return view('laporan.index', [
            'filter' => $filter,
            'tab' => $tab,
            'ambang' => $capaian->ambang(),
            'hasil' => $filter->kurikulum === null ? null : $laporan->susun($tab, $filter->filter),
            'routeLaporan' => $request->route()->getName(),
        ]);
    }
}
