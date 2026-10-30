<?php

namespace App\Http\Controllers\Koprodi;

use App\Http\Controllers\Controller;
use App\Models\Cpmk;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\PeriodeAkademik;
use App\Services\CapaianService;
use App\Services\LaporanCapaian;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard Monitoring Koprodi (bagian 7.6.2 spesifikasi, KF-12): enam kartu statistik dan
 * Ringkasan Capaian CPL kumulatif semua periode, mengikuti kurikulum dan angkatan (KD-23).
 */
class DashboardKoprodiController extends Controller
{
    public function __invoke(Request $request, LaporanCapaian $laporan, CapaianService $capaian): View
    {
        $kurikulum = $this->kurikulumTerpilih($request);
        $periodeAktif = PeriodeAkademik::aktif();
        $daftarAngkatan = Mahasiswa::distinct()->orderByDesc('angkatan')->pluck('angkatan');
        $angkatan = $daftarAngkatan->contains((int) $request->query('angkatan')) ? (int) $request->query('angkatan') : null;

        return view('koprodi.dashboard', [
            'kurikulum' => $kurikulum,
            'daftarKurikulum' => Kurikulum::pilihan(),
            'periodeAktif' => $periodeAktif,
            'pilihanAngkatan' => $daftarAngkatan->mapWithKeys(fn (int $tahun) => [$tahun => 'Angkatan '.$tahun]),
            'angkatan' => $angkatan,
            'jumlah' => $this->jumlahData($kurikulum, $periodeAktif),
            'ambang' => $capaian->ambang(),
            'ringkasan' => $kurikulum === null ? null : $laporan->ringkasanCpl($kurikulum, $angkatan),
        ]);
    }

    /**
     * Jumlah mahasiswa (seluruh prodi), serta jumlah MK, kelas aktif, CPMK, dan CPL di kurikulum
     * terpilih (bagian 7.6.2).
     *
     * @return array<string, int>
     */
    private function jumlahData(?Kurikulum $kurikulum, ?PeriodeAkademik $periodeAktif): array
    {
        $kurikulumId = $kurikulum?->kurikulum_id;

        return [
            'mahasiswa' => Mahasiswa::count(),
            'mata_kuliah' => $kurikulum?->mataKuliah()->count() ?? 0,
            'kelas_aktif' => KelasKuliah::where('periode_akademik_id', $periodeAktif?->periode_akademik_id)
                ->whereHas('mataKuliah', fn (Builder $query) => $query->where('kurikulum_id', $kurikulumId))
                ->count(),
            'cpmk' => Cpmk::whereHas('cpl', fn (Builder $query) => $query->where('kurikulum_id', $kurikulumId))->count(),
            'cpl' => $kurikulum?->cpl()->count() ?? 0,
        ];
    }
}
