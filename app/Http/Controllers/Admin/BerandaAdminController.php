<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\PeriodeAkademik;
use Illuminate\View\View;

/**
 * Beranda Admin (bagian 7.6.3 spesifikasi): ringkasan jumlah data dan pintasan menu.
 * Tidak menampilkan data capaian.
 */
class BerandaAdminController extends Controller
{
    public function __invoke(): View
    {
        $kurikulumTerbaru = Kurikulum::terbaru();
        $periodeAktif = PeriodeAkademik::aktif();

        return view('admin.beranda', [
            'jumlahMahasiswa' => Mahasiswa::count(),
            'jumlahDosen' => Dosen::count(),
            'kurikulumTerbaru' => $kurikulumTerbaru,
            'jumlahMataKuliah' => $kurikulumTerbaru?->mataKuliah()->count() ?? 0,
            'periodeAktif' => $periodeAktif,
            'jumlahKelas' => $periodeAktif?->kelasKuliah()->count() ?? 0,
        ]);
    }
}
