<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\KelasKuliah;
use App\Models\PeriodeAkademik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard Dosen (bagian 7.6.11 spesifikasi): kartu kelas yang diampu pada periode aktif
 * (KD-45).
 */
class DashboardDosenController extends Controller
{
    public function __invoke(Request $request): View
    {
        $periodeAktif = PeriodeAkademik::aktif();

        return view('dosen.dashboard', [
            'periodeAktif' => $periodeAktif,
            'daftarKelas' => KelasKuliah::with(['mataKuliah.kurikulum', 'periodeAkademik'])
                ->withCount(['pesertaKelas', 'rencanaAsesmen'])
                ->where('periode_akademik_id', $periodeAktif?->periode_akademik_id)
                ->whereHas('dosen', fn (Builder $query) => $query->whereKey($request->user()->dosen?->dosen_id))
                ->get()
                ->sortBy('mataKuliah.kode_mata_kuliah'),
        ]);
    }
}
