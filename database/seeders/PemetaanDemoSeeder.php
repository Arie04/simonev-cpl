<?php

namespace Database\Seeders;

use App\Models\BahanKajian;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use App\Models\MetodePenilaian;
use App\Models\PemetaanCpmkMk;
use App\Models\ProfilLulusan;
use App\Models\SubCpmk;
use Illuminate\Database\Seeder;

/**
 * Data contoh pemetaan kurikulum dan Sub-CPMK (iterasi 3, KD-43).
 *
 * Semua bobot memenuhi aturan 5.1: Σ bobot CPMK per MK = 100 dan Σ bobot Sub-CPMK per pasangan
 * CPMK–MK = bobot CPMK-nya. CPMK032 kurikulum 2021 sengaja dipakai di dua MK, seperti CPMK063
 * di contoh APTIKOM, dan kode Sub-CPMK0321 dipakai di dua pasangan.
 */
class PemetaanDemoSeeder extends Seeder
{
    public function run(): void
    {
        $kurikulum2021 = Kurikulum::where('tahun_berlaku', 2021)->firstOrFail();
        $kurikulum2026 = Kurikulum::where('tahun_berlaku', 2026)->firstOrFail();

        $this->petakanCpl($kurikulum2021, [
            'CPL01' => [['PL01', 'PL02', 'PL03'], ['BK04']],
            'CPL02' => [['PL02'], ['BK01', 'BK02']],
            'CPL03' => [['PL01'], ['BK03']],
            'CPL04' => [['PL01', 'PL03'], ['BK04']],
        ]);
        $this->petakanCpl($kurikulum2026, [
            'CPL01' => [['PL01', 'PL02', 'PL03'], ['BK05']],
            'CPL02' => [['PL01', 'PL02'], ['BK02']],
            'CPL03' => [['PL02', 'PL03'], ['BK02', 'BK03']],
            'CPL04' => [['PL01'], ['BK01']],
            'CPL05' => [['PL01', 'PL02'], ['BK03']],
        ]);

        $this->petakanBahanKajian($kurikulum2021, [
            'INF201' => ['BK01' => 100],
            'INF202' => ['BK02' => 100],
            'INF301' => ['BK03' => 70, 'BK02' => 30],
        ]);
        $this->petakanBahanKajian($kurikulum2026, [
            'INF101' => ['BK02' => 100],
            'INF102' => ['BK01' => 100],
            'INF202' => ['BK03' => 80, 'BK02' => 20],
        ]);

        // MK => CPMK => [bobot CPMK, [kode Sub-CPMK => [bobot, deskripsi, metode yang direncanakan]]]
        $this->petakanCpmkDanSubCpmk($kurikulum2021, [
            'INF201' => [
                'CPMK021' => [60, [
                    'Sub-CPMK0211' => [30, 'Mampu menjelaskan cara kerja stack, queue, dan linked list.', ['KUIS', 'UTS']],
                    'Sub-CPMK0212' => [30, 'Mampu menjelaskan struktur tree dan graph beserta penelusurannya.', ['UAS']],
                ]],
                'CPMK012' => [40, [
                    'Sub-CPMK0121' => [40, 'Mampu menyelesaikan tugas implementasi struktur data secara mandiri.', ['TUGAS']],
                ]],
            ],
            'INF202' => [
                'CPMK022' => [60, [
                    'Sub-CPMK0221' => [30, 'Mampu menjelaskan model data relasional dan aljabar relasional.', ['KUIS', 'UTS']],
                    'Sub-CPMK0222' => [30, 'Mampu menormalisasi tabel sampai bentuk normal ketiga.', ['UAS']],
                ]],
                'CPMK032' => [40, [
                    'Sub-CPMK0321' => [40, 'Mampu membuat aplikasi sederhana yang terhubung ke basis data.', ['PRAKTIKUM']],
                ]],
            ],
            'INF301' => [
                'CPMK031' => [35, [
                    'Sub-CPMK0311' => [35, 'Mampu membuat antarmuka web responsif dengan HTML dan CSS.', ['TUGAS', 'UTS']],
                ]],
                'CPMK032' => [40, [
                    'Sub-CPMK0321' => [20, 'Mampu membuat fitur CRUD yang terhubung ke basis data.', ['PROYEK']],
                    'Sub-CPMK0322' => [20, 'Mampu menerapkan autentikasi dan otorisasi sederhana.', ['UAS']],
                ]],
                'CPMK041' => [25, [
                    'Sub-CPMK0411' => [25, 'Mampu mempresentasikan proyek aplikasi web secara tim.', ['PROYEK']],
                ]],
            ],
        ]);
        $this->petakanCpmkDanSubCpmk($kurikulum2026, [
            'INF101' => [
                'CPMK021' => [40, [
                    'Sub-CPMK0211' => [20, 'Mampu menentukan nilai kebenaran proposisi majemuk.', ['KUIS', 'UTS']],
                    'Sub-CPMK0212' => [20, 'Mampu menyusun bukti dengan induksi matematika.', ['UTS']],
                ]],
                'CPMK022' => [35, [
                    'Sub-CPMK0221' => [15, 'Mampu menjelaskan operasi himpunan, relasi, dan fungsi.', ['TUGAS']],
                    'Sub-CPMK0222' => [20, 'Mampu menghitung permutasi dan kombinasi.', ['UAS']],
                ]],
                'CPMK031' => [25, [
                    'Sub-CPMK0311' => [25, 'Mampu memodelkan masalah lintasan terpendek dengan graf.', ['UAS']],
                ]],
            ],
            'INF102' => [
                'CPMK041' => [40, [
                    'Sub-CPMK0411' => [20, 'Mampu menyusun algoritma dengan percabangan dan perulangan.', ['KUIS', 'UTS']],
                    'Sub-CPMK0412' => [20, 'Mampu menyusun fungsi dan algoritma rekursif.', ['UTS']],
                ]],
                'CPMK042' => [40, [
                    'Sub-CPMK0421' => [20, 'Mampu mengolah data dengan array.', ['PRAKTIKUM']],
                    'Sub-CPMK0422' => [20, 'Mampu membuat program utuh untuk studi kasus sederhana.', ['UAS']],
                ]],
                'CPMK011' => [20, [
                    'Sub-CPMK0111' => [20, 'Mampu menyelesaikan tugas pemrograman secara jujur dan tepat waktu.', ['TUGAS']],
                ]],
            ],
            'INF202' => [
                'CPMK051' => [40, [
                    'Sub-CPMK0511' => [20, 'Mampu membuat diagram entitas-relasi dari kebutuhan data.', ['TUGAS']],
                    'Sub-CPMK0512' => [20, 'Mampu menormalisasi skema basis data.', ['UTS']],
                ]],
                'CPMK052' => [40, [
                    'Sub-CPMK0521' => [20, 'Mampu menulis perintah DDL dan DML.', ['PRAKTIKUM']],
                    'Sub-CPMK0522' => [20, 'Mampu menulis kueri join dan agregasi.', ['UAS']],
                ]],
                'CPMK032' => [20, [
                    'Sub-CPMK0321' => [20, 'Mampu menganalisis kebutuhan data sebuah organisasi.', ['PROYEK']],
                ]],
            ],
        ]);
    }

    /**
     * @param  array<string, array{0: list<string>, 1: list<string>}>  $pemetaan  kode CPL => [kode PL, kode BK]
     */
    private function petakanCpl(Kurikulum $kurikulum, array $pemetaan): void
    {
        foreach ($pemetaan as $kodeCpl => [$daftarKodePl, $daftarKodeBk]) {
            $cpl = Cpl::where('kurikulum_id', $kurikulum->kurikulum_id)->where('kode_cpl', $kodeCpl)->firstOrFail();

            $cpl->profilLulusan()->sync(
                ProfilLulusan::where('kurikulum_id', $kurikulum->kurikulum_id)->whereIn('kode_pl', $daftarKodePl)->pluck('profil_lulusan_id')
            );
            $cpl->bahanKajian()->sync(
                BahanKajian::where('kurikulum_id', $kurikulum->kurikulum_id)->whereIn('kode_bk', $daftarKodeBk)->pluck('bahan_kajian_id')
            );
        }
    }

    /**
     * Method protected di bawah ini dipakai juga oleh SimulasiKurikulumSeeder (turunan class ini).
     *
     * @param  array<string, array<string, int>>  $pemetaan  kode MK => [kode BK => bobot]
     */
    protected function petakanBahanKajian(Kurikulum $kurikulum, array $pemetaan): void
    {
        foreach ($pemetaan as $kodeMataKuliah => $daftarBobot) {
            $mataKuliah = $this->mataKuliah($kurikulum, $kodeMataKuliah);
            $dataPivot = [];

            foreach ($daftarBobot as $kodeBk => $bobot) {
                $idBahanKajian = BahanKajian::where('kurikulum_id', $kurikulum->kurikulum_id)->where('kode_bk', $kodeBk)->value('bahan_kajian_id');
                $dataPivot[$idBahanKajian] = ['bobot_bk_mk' => $bobot];
            }

            $mataKuliah->bahanKajian()->sync($dataPivot);
        }
    }

    /**
     * @param  array<string, array<string, array>>  $pemetaan  kode MK => [kode CPMK => [bobot CPMK, [kode Sub-CPMK => [bobot, deskripsi, metode]]]]
     */
    protected function petakanCpmkDanSubCpmk(Kurikulum $kurikulum, array $pemetaan): void
    {
        foreach ($pemetaan as $kodeMataKuliah => $daftarCpmk) {
            $mataKuliah = $this->mataKuliah($kurikulum, $kodeMataKuliah);

            foreach ($daftarCpmk as $kodeCpmk => [$bobotCpmk, $daftarSubCpmk]) {
                $cpmk = Cpmk::where('kode_cpmk', $kodeCpmk)
                    ->whereHas('cpl', fn ($query) => $query->where('kurikulum_id', $kurikulum->kurikulum_id))
                    ->firstOrFail();

                $pasangan = PemetaanCpmkMk::firstOrNew(['cpmk_id' => $cpmk->cpmk_id, 'mata_kuliah_id' => $mataKuliah->mata_kuliah_id]);
                $pasangan->fill(['bobot_cpmk' => $bobotCpmk])->save();

                foreach ($daftarSubCpmk as $kodeSubCpmk => [$bobotSub, $deskripsi, $daftarKodeMetode]) {
                    $subCpmk = SubCpmk::updateOrCreate(
                        ['pemetaan_cpmk_mk_id' => $pasangan->pemetaan_cpmk_mk_id, 'kode_sub_cpmk' => $kodeSubCpmk],
                        ['deskripsi_sub_cpmk' => $deskripsi, 'bobot_sub_cpmk' => $bobotSub, 'materi' => $deskripsi],
                    );

                    // Rincian penilaian: metode yang direncanakan untuk Sub-CPMK ini.
                    $subCpmk->metodePenilaian()->sync(
                        MetodePenilaian::whereIn('kode_metode', $daftarKodeMetode)->get()
                            ->mapWithKeys(fn (MetodePenilaian $metode) => [$metode->metode_penilaian_id => [
                                'teknik_penilaian' => in_array($metode->kode_metode, ['KUIS', 'UTS', 'UAS'], true) ? 'Tes tertulis' : 'Penilaian unjuk kerja',
                                'instrumen' => in_array($metode->kode_metode, ['KUIS', 'UTS', 'UAS'], true) ? 'Soal uraian' : 'Rubrik',
                            ]])
                    );
                }
            }
        }
    }

    protected function mataKuliah(Kurikulum $kurikulum, string $kodeMataKuliah): MataKuliah
    {
        return MataKuliah::where('kurikulum_id', $kurikulum->kurikulum_id)->where('kode_mata_kuliah', $kodeMataKuliah)->firstOrFail();
    }
}
