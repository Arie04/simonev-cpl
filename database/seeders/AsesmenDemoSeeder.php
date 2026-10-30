<?php

namespace Database\Seeders;

use App\Models\KelasKuliah;
use App\Models\MetodePenilaian;
use App\Models\RencanaAsesmen;
use App\Models\SubCpmk;
use Illuminate\Database\Seeder;

/**
 * Data contoh rencana asesmen (iterasi 5, KD-43). Semua bobot memenuhi aturan 5.1:
 * - Σ bobot asesmen per kelas = 100;
 * - Σ kontribusi per asesmen = bobot asesmennya;
 * - Σ kontribusi per Sub-CPMK = bobot_sub_cpmk (target kurikulum).
 * Metode setiap asesmen juga termasuk metode yang direncanakan untuk Sub-CPMK-nya.
 */
class AsesmenDemoSeeder extends Seeder
{
    /**
     * kode MK + tahun kurikulum => daftar [nama, kode metode, bobot, nilai maks, tanggal, [kode Sub-CPMK => kontribusi]]
     */
    private const RENCANA = [
        'INF201-2021' => [
            ['Kuis 1', 'KUIS', 10, 100, '2026-03-02', ['Sub-CPMK0211' => 10]],
            ['Tugas Implementasi', 'TUGAS', 40, 100, '2026-03-23', ['Sub-CPMK0121' => 40]],
            ['UTS', 'UTS', 20, 100, '2026-04-13', ['Sub-CPMK0211' => 20]],
            ['UAS', 'UAS', 30, 100, '2026-06-15', ['Sub-CPMK0212' => 30]],
        ],
        'INF202-2021' => [
            ['Kuis 1', 'KUIS', 10, 100, '2026-09-21', ['Sub-CPMK0221' => 10]],
            ['UTS', 'UTS', 20, 100, '2026-10-26', ['Sub-CPMK0221' => 20]],
            ['Praktikum Aplikasi', 'PRAKTIKUM', 40, 100, '2026-11-30', ['Sub-CPMK0321' => 40]],
            ['UAS', 'UAS', 30, 100, '2026-12-21', ['Sub-CPMK0222' => 30]],
        ],
        'INF301-2021' => [
            ['Tugas Antarmuka', 'TUGAS', 15, 100, '2026-09-28', ['Sub-CPMK0311' => 15]],
            ['UTS', 'UTS', 20, 100, '2026-10-26', ['Sub-CPMK0311' => 20]],
            ['Proyek Aplikasi Web', 'PROYEK', 45, 100, '2026-12-07', ['Sub-CPMK0321' => 20, 'Sub-CPMK0411' => 25]],
            ['UAS', 'UAS', 20, 100, '2026-12-21', ['Sub-CPMK0322' => 20]],
        ],
        'INF101-2026' => [
            ['Kuis 1', 'KUIS', 10, 50, '2026-09-14', ['Sub-CPMK0211' => 10]],
            ['Tugas 1', 'TUGAS', 15, 100, '2026-10-05', ['Sub-CPMK0221' => 15]],
            ['UTS', 'UTS', 30, 100, '2026-10-26', ['Sub-CPMK0211' => 10, 'Sub-CPMK0212' => 20]],
            ['UAS', 'UAS', 45, 100, '2026-12-21', ['Sub-CPMK0222' => 20, 'Sub-CPMK0311' => 25]],
        ],
        'INF102-2026' => [
            ['Kuis 1', 'KUIS', 10, 20, '2026-09-14', ['Sub-CPMK0411' => 10]],
            ['Tugas Mandiri', 'TUGAS', 20, 100, '2026-10-05', ['Sub-CPMK0111' => 20]],
            ['Praktikum Array', 'PRAKTIKUM', 20, 100, '2026-10-19', ['Sub-CPMK0421' => 20]],
            ['UTS', 'UTS', 30, 100, '2026-10-26', ['Sub-CPMK0411' => 10, 'Sub-CPMK0412' => 20]],
            ['UAS', 'UAS', 20, 100, '2026-12-21', ['Sub-CPMK0422' => 20]],
        ],
    ];

    public function run(): void
    {
        foreach (KelasKuliah::with('mataKuliah.kurikulum')->get() as $kelas) {
            $kunci = $kelas->mataKuliah->kode_mata_kuliah.'-'.$kelas->mataKuliah->kurikulum->tahun_berlaku;

            foreach (self::RENCANA[$kunci] ?? [] as [$nama, $kodeMetode, $bobot, $nilaiMaksimum, $tanggal, $kontribusiPerKode]) {
                $rencanaAsesmen = RencanaAsesmen::updateOrCreate(
                    ['kelas_kuliah_id' => $kelas->kelas_kuliah_id, 'nama_asesmen' => $nama],
                    [
                        'metode_penilaian_id' => MetodePenilaian::where('kode_metode', $kodeMetode)->value('metode_penilaian_id'),
                        'bobot_asesmen' => $bobot,
                        'nilai_maksimum' => $nilaiMaksimum,
                        'tanggal_asesmen' => $tanggal,
                    ],
                );

                $rencanaAsesmen->petakanSubCpmk($this->kontribusiPerId($kelas, $kontribusiPerKode));
            }
        }
    }

    /**
     * Mengubah kode Sub-CPMK menjadi id Sub-CPMK milik MK kelas ini.
     *
     * @param  array<string, int>  $kontribusiPerKode
     * @return array<string, int>
     */
    private function kontribusiPerId(KelasKuliah $kelas, array $kontribusiPerKode): array
    {
        $hasil = [];

        foreach ($kontribusiPerKode as $kodeSubCpmk => $bobot) {
            $idSubCpmk = SubCpmk::where('kode_sub_cpmk', $kodeSubCpmk)
                ->whereHas('pemetaanCpmkMk', fn ($query) => $query->where('mata_kuliah_id', $kelas->mata_kuliah_id))
                ->value('sub_cpmk_id');

            $hasil[$idSubCpmk] = $bobot;
        }

        return $hasil;
    }
}
