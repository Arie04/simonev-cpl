<?php

namespace App\Http\Controllers\Koprodi;

use App\Http\Controllers\Controller;
use App\Models\Cpmk;
use App\Models\Kurikulum;
use App\Models\SubCpmk;
use App\Services\MatriksPemetaan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Data Kurikulum untuk Koprodi (bagian 7.6.12 spesifikasi): data kurikulum, evaluasi kurikulum,
 * dan matriks pemetaan secara read-only, dengan pemilih kurikulum.
 */
class DataKurikulumController extends Controller
{
    public const TAB = [
        'ringkasan' => 'Ringkasan',
        'evaluasi' => 'Evaluasi',
        'profil-lulusan' => 'Profil Lulusan',
        'cpl' => 'CPL',
        'bahan-kajian' => 'Bahan Kajian',
        'mata-kuliah' => 'Mata Kuliah',
        'cpmk' => 'CPMK',
        'sub-cpmk' => 'Sub-CPMK',
        'pemetaan' => 'Pemetaan',
    ];

    public function __invoke(Request $request): View
    {
        $kurikulum = $this->kurikulumTerpilih($request);
        $tab = array_key_exists((string) $request->query('tab'), self::TAB) ? $request->query('tab') : 'ringkasan';

        return view('koprodi.kurikulum', [
            'kurikulum' => $kurikulum,
            'daftarKurikulum' => Kurikulum::pilihan(),
            'tab' => $tab,
            'data' => $kurikulum === null ? [] : $this->dataTab($tab, $kurikulum, $request),
        ]);
    }

    /**
     * Data yang dibutuhkan satu tab saja, supaya halaman tidak memuat semua data sekaligus.
     */
    private function dataTab(string $tab, Kurikulum $kurikulum, Request $request): array
    {
        return match ($tab) {
            'ringkasan' => [
                'programStudi' => $kurikulum->programStudi,
                'jumlah' => [
                    'Profil Lulusan' => $kurikulum->profilLulusan()->count(),
                    'CPL' => $kurikulum->cpl()->count(),
                    'Bahan Kajian' => $kurikulum->bahanKajian()->count(),
                    'Mata Kuliah' => $kurikulum->mataKuliah()->count(),
                    'CPMK' => $this->cpmkKurikulum($kurikulum)->count(),
                ],
            ],
            'evaluasi' => ['daftarEvaluasi' => $kurikulum->evaluasiKurikulum()->orderBy('created_at')->get()],
            'profil-lulusan' => ['daftarProfilLulusan' => $kurikulum->profilLulusan()->with('profesi')->orderBy('kode_pl')->get()],
            'cpl' => ['daftarCpl' => $kurikulum->cpl()->with('cpmk')->orderBy('kode_cpl')->get()],
            'bahan-kajian' => ['daftarBahanKajian' => $kurikulum->bahanKajian()->orderBy('kode_bk')->get()],
            'mata-kuliah' => [
                'daftarMataKuliah' => $kurikulum->mataKuliah()
                    ->with(['pemetaanCpmkMk.cpmk.cpl', 'pemetaanCpmkMk.subCpmk'])
                    ->orderBy('semester')
                    ->orderBy('kode_mata_kuliah')
                    ->get(),
            ],
            'cpmk' => ['daftarCpmk' => $this->cpmkKurikulum($kurikulum)->with(['cpl', 'mataKuliah'])->orderBy('kode_cpmk')->get()],
            'sub-cpmk' => [
                'daftarSubCpmk' => SubCpmk::with(['pemetaanCpmkMk.cpmk', 'pemetaanCpmkMk.mataKuliah'])
                    ->whereHas('pemetaanCpmkMk.mataKuliah', fn (Builder $query) => $query->where('kurikulum_id', $kurikulum->kurikulum_id))
                    ->get()
                    ->sortBy(fn (SubCpmk $subCpmk) => $subCpmk->pemetaanCpmkMk->mataKuliah->kode_mata_kuliah.' '.$subCpmk->pemetaanCpmkMk->cpmk->kode_cpmk.' '.$subCpmk->kode_sub_cpmk),
            ],
            'pemetaan' => ['matriks' => new MatriksPemetaan(MatriksPemetaan::jenisAtauBawaan($request->query('jenis')), $kurikulum)],
        };
    }

    private function cpmkKurikulum(Kurikulum $kurikulum): Builder
    {
        return Cpmk::whereHas('cpl', fn (Builder $query) => $query->where('kurikulum_id', $kurikulum->kurikulum_id));
    }
}
