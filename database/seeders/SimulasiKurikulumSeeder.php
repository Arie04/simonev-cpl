<?php

namespace Database\Seeders;

use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Kurikulum;
use App\Models\MataKuliah;

/**
 * Bagian kurikulum dari data simulasi dua tahun (KD-78). Kurikulum 2021 di data demo hanya punya
 * tiga MK, jadi simulasi menambah tiga CPMK dan enam MK supaya setiap semester punya kelas.
 *
 * Class ini turunan PemetaanDemoSeeder supaya bisa memakai ulang cara memetakan BK–MK serta
 * CPMK–MK dan Sub-CPMK. Semua bobot memenuhi aturan 5.1: Σ bobot CPMK per MK = 100 dan
 * Σ bobot Sub-CPMK per pasangan CPMK–MK = bobot CPMK-nya.
 */
class SimulasiKurikulumSeeder extends PemetaanDemoSeeder
{
    /** CPMK baru: kode CPMK => [kode CPL, deskripsi]. */
    private const CPMK_BARU = [
        'CPMK023' => ['CPL02', 'Mampu menjelaskan konsep algoritma, tipe data, dan struktur kontrol.'],
        'CPMK033' => ['CPL03', 'Mampu menerapkan prinsip keamanan pada aplikasi web.'],
        'CPMK034' => ['CPL03', 'Mampu menerapkan pemrograman berorientasi objek dalam membangun aplikasi.'],
    ];

    /** MK baru: [kode, nama, semester, SKS, jenis, level Bloom]. */
    private const MATA_KULIAH_BARU = [
        ['INF103', 'Algoritma dan Pemrograman', 1, 4, 'Wajib', 'C3'],
        ['INF105', 'Pemrograman Berorientasi Objek', 2, 3, 'Wajib', 'C3'],
        ['INF203', 'Interaksi Manusia dan Komputer', 3, 3, 'Wajib', 'C4'],
        ['INF204', 'Rekayasa Perangkat Lunak', 4, 3, 'Wajib', 'C4'],
        ['INF302', 'Keamanan Aplikasi Web', 5, 3, 'Wajib', 'C4'],
        ['INF303', 'Proyek Perangkat Lunak', 6, 4, 'Wajib', 'C6'],
    ];

    /** Bobot bahan kajian per MK: kode MK => [kode BK => bobot]. */
    private const BAHAN_KAJIAN = [
        'INF103' => ['BK01' => 100],
        'INF105' => ['BK01' => 40, 'BK03' => 60],
        'INF203' => ['BK03' => 80, 'BK04' => 20],
        'INF204' => ['BK03' => 70, 'BK04' => 30],
        'INF302' => ['BK03' => 70, 'BK04' => 30],
        'INF303' => ['BK03' => 60, 'BK02' => 40],
    ];

    /** MK => CPMK => [bobot CPMK, [kode Sub-CPMK => [bobot, deskripsi, metode yang direncanakan]]] */
    private const CPMK_DAN_SUB_CPMK = [
        'INF103' => [
            'CPMK023' => [60, [
                'Sub-CPMK0231' => [30, 'Mampu menyusun algoritma dengan percabangan dan perulangan.', ['KUIS', 'UTS']],
                'Sub-CPMK0232' => [30, 'Mampu memecah masalah menjadi fungsi dan prosedur.', ['UAS']],
            ]],
            'CPMK012' => [40, [
                'Sub-CPMK0122' => [40, 'Mampu menyelesaikan tugas pemrograman secara mandiri dan tepat waktu.', ['TUGAS']],
            ]],
        ],
        'INF105' => [
            'CPMK034' => [50, [
                'Sub-CPMK0341' => [25, 'Mampu membuat class dan objek dengan enkapsulasi.', ['TUGAS', 'UTS']],
                'Sub-CPMK0342' => [25, 'Mampu menerapkan pewarisan dan polimorfisme.', ['UAS']],
            ]],
            'CPMK023' => [20, [
                'Sub-CPMK0233' => [20, 'Mampu memilih struktur kontrol dan koleksi data yang tepat.', ['KUIS']],
            ]],
            'CPMK042' => [30, [
                'Sub-CPMK0421' => [30, 'Mampu membangun aplikasi berorientasi objek secara berkelompok.', ['PROYEK']],
            ]],
        ],
        'INF203' => [
            'CPMK031' => [50, [
                'Sub-CPMK0312' => [25, 'Mampu menganalisis kebutuhan pengguna dan membuat wireframe.', ['TUGAS']],
                'Sub-CPMK0313' => [25, 'Mampu mengevaluasi kegunaan antarmuka dengan evaluasi heuristik.', ['UTS', 'UAS']],
            ]],
            'CPMK041' => [30, [
                'Sub-CPMK0412' => [30, 'Mampu mempresentasikan purwarupa antarmuka.', ['PROYEK']],
            ]],
            'CPMK011' => [20, [
                'Sub-CPMK0111' => [20, 'Mampu menerapkan etika dan privasi dalam perancangan antarmuka.', ['KUIS']],
            ]],
        ],
        'INF204' => [
            'CPMK042' => [40, [
                'Sub-CPMK0422' => [40, 'Mampu bekerja dalam tim pengembang dengan metode Scrum.', ['PROYEK']],
            ]],
            'CPMK032' => [40, [
                'Sub-CPMK0323' => [20, 'Mampu merancang aplikasi web dan basis datanya dengan diagram UML.', ['TUGAS', 'UTS']],
                'Sub-CPMK0324' => [20, 'Mampu menguji fitur aplikasi web dengan kasus uji.', ['UAS']],
            ]],
            'CPMK011' => [20, [
                'Sub-CPMK0112' => [20, 'Mampu menerapkan kode etik profesi dalam proyek perangkat lunak.', ['TUGAS']],
            ]],
        ],
        'INF302' => [
            'CPMK033' => [70, [
                'Sub-CPMK0331' => [35, 'Mampu mengidentifikasi kerentanan umum aplikasi web.', ['KUIS', 'UTS']],
                'Sub-CPMK0332' => [35, 'Mampu menerapkan validasi masukan dan enkripsi data.', ['PRAKTIKUM', 'UAS']],
            ]],
            'CPMK011' => [30, [
                'Sub-CPMK0113' => [30, 'Mampu menerapkan etika dalam pengujian keamanan aplikasi.', ['TUGAS']],
            ]],
        ],
        'INF303' => [
            'CPMK032' => [40, [
                'Sub-CPMK0325' => [40, 'Mampu membangun aplikasi web utuh yang terhubung ke basis data.', ['PROYEK']],
            ]],
            'CPMK042' => [30, [
                'Sub-CPMK0423' => [30, 'Mampu berkolaborasi memakai sistem kontrol versi.', ['TUGAS']],
            ]],
            'CPMK041' => [30, [
                'Sub-CPMK0413' => [30, 'Mampu mempresentasikan hasil proyek kepada pengguna.', ['UAS']],
            ]],
        ],
    ];

    public function run(): void
    {
        $kurikulum = Kurikulum::where('tahun_berlaku', 2021)->firstOrFail();

        foreach (self::CPMK_BARU as $kodeCpmk => [$kodeCpl, $deskripsi]) {
            $cpl = Cpl::where('kurikulum_id', $kurikulum->kurikulum_id)->where('kode_cpl', $kodeCpl)->firstOrFail();

            Cpmk::updateOrCreate(['cpl_id' => $cpl->cpl_id, 'kode_cpmk' => $kodeCpmk], ['deskripsi_cpmk' => $deskripsi]);
        }

        foreach (self::MATA_KULIAH_BARU as [$kode, $nama, $semester, $sks, $jenis, $levelBloom]) {
            MataKuliah::updateOrCreate(
                ['kurikulum_id' => $kurikulum->kurikulum_id, 'kode_mata_kuliah' => $kode],
                [
                    'nama_mata_kuliah' => $nama,
                    'semester' => $semester,
                    'jumlah_sks' => $sks,
                    'jenis_mata_kuliah' => $jenis,
                    'level_bloom_mk' => $levelBloom,
                ],
            );
        }

        $this->petakanBahanKajian($kurikulum, self::BAHAN_KAJIAN);
        $this->petakanCpmkDanSubCpmk($kurikulum, self::CPMK_DAN_SUB_CPMK);
    }
}
