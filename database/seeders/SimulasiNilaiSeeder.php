<?php

namespace Database\Seeders;

use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use App\Models\MetodePenilaian;
use App\Models\PeriodeAkademik;
use App\Models\RencanaAsesmen;
use App\Models\SubCpmk;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Bagian asesmen dari data simulasi dua tahun (KD-78, KD-79): rencana asesmen setiap kelas
 * simulasi dan nilai pesertanya.
 *
 * Nilai tidak diacak, tetapi dihitung dengan rumus tetap supaya hasilnya selalu sama setiap kali
 * seeder dijalankan:
 *
 *     persen = kemampuan dasar − perubahan per semester × jarak semester ke 2025/2026 Genap
 *              + penyesuaian MK + penyesuaian metode + tambahan mengulang + variasi
 *
 * Kemampuan dasar memakai daftar yang sama dengan NilaiDemoSeeder, jadi pada 2025/2026 Genap
 * nilai simulasi menyambung dengan nilai kelas demo. Rumus ini hanya untuk membuat data contoh;
 * perhitungan capaian tetap di CapaianService.
 */
class SimulasiNilaiSeeder extends Seeder
{
    /**
     * Rencana asesmen per MK: [nama, kode metode, bobot, nilai maks, minggu ke-, [kode Sub-CPMK => kontribusi]].
     * Semua bobot memenuhi aturan 5.1: Σ bobot asesmen = 100, Σ kontribusi per asesmen = bobotnya,
     * dan Σ kontribusi per Sub-CPMK = bobot_sub_cpmk.
     */
    private const RENCANA = [
        'INF103' => [
            ['Kuis 1', 'KUIS', 10, 20, 3, ['Sub-CPMK0231' => 10]],
            ['Tugas Pemrograman', 'TUGAS', 40, 100, 6, ['Sub-CPMK0122' => 40]],
            ['UTS', 'UTS', 20, 100, 8, ['Sub-CPMK0231' => 20]],
            ['UAS', 'UAS', 30, 100, 16, ['Sub-CPMK0232' => 30]],
        ],
        'INF105' => [
            ['Kuis 1', 'KUIS', 20, 100, 3, ['Sub-CPMK0233' => 20]],
            ['Tugas Class dan Objek', 'TUGAS', 10, 100, 5, ['Sub-CPMK0341' => 10]],
            ['UTS', 'UTS', 15, 100, 8, ['Sub-CPMK0341' => 15]],
            ['Proyek Kelompok', 'PROYEK', 30, 100, 14, ['Sub-CPMK0421' => 30]],
            ['UAS', 'UAS', 25, 100, 16, ['Sub-CPMK0342' => 25]],
        ],
        'INF201' => [
            ['Kuis 1', 'KUIS', 10, 100, 3, ['Sub-CPMK0211' => 10]],
            ['Tugas Implementasi', 'TUGAS', 40, 100, 6, ['Sub-CPMK0121' => 40]],
            ['UTS', 'UTS', 20, 100, 8, ['Sub-CPMK0211' => 20]],
            ['UAS', 'UAS', 30, 100, 16, ['Sub-CPMK0212' => 30]],
        ],
        'INF202' => [
            ['Kuis 1', 'KUIS', 10, 100, 3, ['Sub-CPMK0221' => 10]],
            ['UTS', 'UTS', 20, 100, 8, ['Sub-CPMK0221' => 20]],
            ['Praktikum Aplikasi', 'PRAKTIKUM', 40, 100, 13, ['Sub-CPMK0321' => 40]],
            ['UAS', 'UAS', 30, 100, 16, ['Sub-CPMK0222' => 30]],
        ],
        'INF203' => [
            ['Kuis Etika', 'KUIS', 20, 50, 3, ['Sub-CPMK0111' => 20]],
            ['Tugas Wireframe', 'TUGAS', 25, 100, 5, ['Sub-CPMK0312' => 25]],
            ['UTS', 'UTS', 10, 100, 8, ['Sub-CPMK0313' => 10]],
            ['Presentasi Purwarupa', 'PROYEK', 30, 100, 14, ['Sub-CPMK0412' => 30]],
            ['UAS', 'UAS', 15, 100, 16, ['Sub-CPMK0313' => 15]],
        ],
        'INF204' => [
            ['Tugas UML dan Etika', 'TUGAS', 30, 100, 5, ['Sub-CPMK0323' => 10, 'Sub-CPMK0112' => 20]],
            ['UTS', 'UTS', 10, 100, 8, ['Sub-CPMK0323' => 10]],
            ['Proyek Tim', 'PROYEK', 40, 100, 14, ['Sub-CPMK0422' => 40]],
            ['UAS', 'UAS', 20, 100, 16, ['Sub-CPMK0324' => 20]],
        ],
        'INF301' => [
            ['Tugas Antarmuka', 'TUGAS', 15, 100, 4, ['Sub-CPMK0311' => 15]],
            ['UTS', 'UTS', 20, 100, 8, ['Sub-CPMK0311' => 20]],
            ['Proyek Aplikasi Web', 'PROYEK', 45, 100, 14, ['Sub-CPMK0321' => 20, 'Sub-CPMK0411' => 25]],
            ['UAS', 'UAS', 20, 100, 16, ['Sub-CPMK0322' => 20]],
        ],
        'INF302' => [
            ['Kuis 1', 'KUIS', 10, 100, 3, ['Sub-CPMK0331' => 10]],
            ['Tugas Etika Pengujian', 'TUGAS', 30, 100, 5, ['Sub-CPMK0113' => 30]],
            ['UTS', 'UTS', 25, 100, 8, ['Sub-CPMK0331' => 25]],
            ['Praktikum Enkripsi', 'PRAKTIKUM', 15, 100, 12, ['Sub-CPMK0332' => 15]],
            ['UAS', 'UAS', 20, 100, 16, ['Sub-CPMK0332' => 20]],
        ],
        'INF303' => [
            ['Tugas Kolaborasi', 'TUGAS', 30, 100, 6, ['Sub-CPMK0423' => 30]],
            ['Proyek Aplikasi', 'PROYEK', 40, 100, 14, ['Sub-CPMK0325' => 40]],
            ['UAS Presentasi Proyek', 'UAS', 30, 100, 16, ['Sub-CPMK0413' => 30]],
        ],
    ];

    /**
     * Perubahan kemampuan setiap semester (poin), sesuai urutan NPM dalam angkatan. Positif berarti
     * makin baik, misalnya urutan pertama naik 4 poin per semester; negatif berarti menurun.
     */
    private const PERUBAHAN_PER_SEMESTER = [4, 2, 3, -2, 1, 2, 0, 3];

    /** Semester terakhir simulasi (2025/2026 Genap), dihitung dari 0 = 2024/2025 Ganjil. */
    private const URUTAN_SEMESTER_TERAKHIR = 3;

    /** Tingkat kesulitan MK dalam poin: negatif berarti nilainya cenderung lebih rendah. */
    private const PENYESUAIAN_MATA_KULIAH = [
        'INF103' => -6, 'INF105' => -3, 'INF201' => -4, 'INF202' => 0, 'INF203' => 3,
        'INF204' => 2, 'INF301' => 0, 'INF302' => -5, 'INF303' => 4,
    ];

    /** Tugas dan proyek biasanya bernilai lebih tinggi daripada ujian. */
    private const PENYESUAIAN_METODE = ['KUIS' => 0, 'TUGAS' => 5, 'PRAKTIKUM' => 3, 'PROYEK' => 4, 'UTS' => -3, 'UAS' => -5];

    /** Mahasiswa yang mengulang sudah pernah mempelajari materinya, jadi nilainya naik. */
    private const TAMBAHAN_MENGULANG = 12;

    /** Mahasiswa yang tidak ikut satu asesmen, jadi nilainya kosong: [NPM, kode MK, periode, asesmen]. */
    private const TIDAK_IKUT = [
        ['9992400006', 'INF105', '2024/2025 Genap', 'Kuis 1'],
        ['9992500003', 'INF103', '2025/2026 Ganjil', 'UTS'],
    ];

    public function run(): void
    {
        // Urutan setiap mahasiswa contoh (NPM diawali 999) di angkatannya, urut NPM, sama seperti urutan
        // di NilaiDemoSeeder. Mahasiswa lain tidak dihitung supaya tidak menggeser urutan ini.
        $urutanDalamAngkatan = [];

        foreach (Mahasiswa::where('nim', 'like', '999%')->orderBy('nim')->get()->groupBy('angkatan') as $daftarMahasiswa) {
            foreach ($daftarMahasiswa->values() as $urutan => $mahasiswa) {
                $urutanDalamAngkatan[$mahasiswa->nim] = $urutan;
            }
        }

        foreach (SimulasiPerkuliahanSeeder::DAFTAR_KELAS as [$kodeMataKuliah, $tahunAkademik, $semester, $daftarNidn, $angkatan, $nimMengulang]) {
            $kelas = SimulasiPerkuliahanSeeder::cariKelas($kodeMataKuliah, $tahunAkademik, $semester);

            $this->isiRencanaAsesmen($kelas, $kodeMataKuliah);
            $this->isiNilai($kelas, $kodeMataKuliah, $nimMengulang, $urutanDalamAngkatan);
        }
    }

    private function isiRencanaAsesmen(KelasKuliah $kelas, string $kodeMataKuliah): void
    {
        $awalPeriode = $this->awalPeriode($kelas->periodeAkademik);

        foreach (self::RENCANA[$kodeMataKuliah] as [$nama, $kodeMetode, $bobot, $nilaiMaksimum, $minggu, $kontribusiPerKode]) {
            $rencanaAsesmen = RencanaAsesmen::updateOrCreate(
                ['kelas_kuliah_id' => $kelas->kelas_kuliah_id, 'nama_asesmen' => $nama],
                [
                    'metode_penilaian_id' => MetodePenilaian::where('kode_metode', $kodeMetode)->value('metode_penilaian_id'),
                    'bobot_asesmen' => $bobot,
                    'nilai_maksimum' => $nilaiMaksimum,
                    'tanggal_asesmen' => $awalPeriode->copy()->addWeeks($minggu - 1)->toDateString(),
                ],
            );

            $rencanaAsesmen->petakanSubCpmk($this->kontribusiPerId($kelas, $kontribusiPerKode));
        }
    }

    /**
     * @param  list<string>  $nimMengulang
     * @param  array<string, int>  $urutanDalamAngkatan  NPM => urutan di angkatannya
     */
    private function isiNilai(KelasKuliah $kelas, string $kodeMataKuliah, array $nimMengulang, array $urutanDalamAngkatan): void
    {
        $kelas->load(['rencanaAsesmen.metodePenilaian', 'pesertaKelas.mahasiswa']);
        $periode = $kelas->periodeAkademik;
        $jarakKeSemesterTerakhir = self::URUTAN_SEMESTER_TERAKHIR - $this->urutanSemester($periode);

        // Hanya asesmen dari rencana simulasi; asesmen yang ditambahkan lewat aplikasi tidak disentuh.
        $daftarAsesmen = $kelas->rencanaAsesmen->whereIn('nama_asesmen', array_column(self::RENCANA[$kodeMataKuliah], 0));

        foreach ($daftarAsesmen as $asesmen) {
            $nilaiPerPeserta = [];

            foreach ($kelas->pesertaKelas as $peserta) {
                $nim = $peserta->mahasiswa->nim;

                // Peserta yang bukan mahasiswa contoh (mis. ditambahkan Admin lewat aplikasi) tidak diberi
                // nilai simulasi; nilainya tidak disentuh.
                if (! isset($urutanDalamAngkatan[$nim])) {
                    continue;
                }

                if (in_array([$nim, $kodeMataKuliah, $periode->nama(), $asesmen->nama_asesmen], self::TIDAK_IKUT, true)) {
                    $nilaiPerPeserta[$peserta->peserta_kelas_id] = null;

                    continue;
                }

                $urutan = $urutanDalamAngkatan[$nim];
                $persen = NilaiDemoSeeder::KEMAMPUAN[$urutan % count(NilaiDemoSeeder::KEMAMPUAN)]
                    - self::PERUBAHAN_PER_SEMESTER[$urutan % count(self::PERUBAHAN_PER_SEMESTER)] * $jarakKeSemesterTerakhir
                    + self::PENYESUAIAN_MATA_KULIAH[$kodeMataKuliah]
                    + self::PENYESUAIAN_METODE[$asesmen->metodePenilaian->kode_metode]
                    + (in_array($nim, $nimMengulang, true) ? self::TAMBAHAN_MENGULANG : 0)
                    + $this->variasi($nim, $kodeMataKuliah, $periode->nama(), $asesmen->nama_asesmen);

                $nilaiPerPeserta[$peserta->peserta_kelas_id] = round(max(0, min(100, $persen)) * $asesmen->nilai_maksimum / 100, 1);
            }

            $asesmen->simpanNilai($nilaiPerPeserta);
        }
    }

    /**
     * Variasi −6 sampai +6 poin yang tampak acak tetapi selalu sama: crc32 mengubah teks
     * (NPM, MK, periode, asesmen) menjadi angka yang tetap untuk teks yang sama.
     */
    private function variasi(string ...$bagian): int
    {
        return crc32(implode('|', $bagian)) % 13 - 6;
    }

    /**
     * Urutan semester simulasi: 2024/2025 Ganjil = 0, 2024/2025 Genap = 1, 2025/2026 Ganjil = 2,
     * dan 2025/2026 Genap = 3.
     */
    private function urutanSemester(PeriodeAkademik $periode): int
    {
        $tahunAwal = (int) substr($periode->tahun_akademik, 0, 4);

        return ($tahunAwal - 2024) * 2 + ($periode->semester_periode === PeriodeAkademik::GENAP ? 1 : 0);
    }

    /**
     * Hari pertama kuliah: Senin pertama mulai 1 September (Ganjil) atau 16 Februari (Genap), mendekati
     * tanggal asesmen kelas demo. Karena kuliah mulai hari Senin, asesmen juga jatuh pada hari Senin.
     */
    private function awalPeriode(PeriodeAkademik $periode): Carbon
    {
        $tahunAwal = (int) substr($periode->tahun_akademik, 0, 4);
        $awal = $periode->semester_periode === PeriodeAkademik::GANJIL
            ? Carbon::create($tahunAwal, 9, 1)
            : Carbon::create($tahunAwal + 1, 2, 16);

        return $awal->isMonday() ? $awal : $awal->next(Carbon::MONDAY);
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
            $subCpmk = SubCpmk::where('kode_sub_cpmk', $kodeSubCpmk)
                ->whereHas('pemetaanCpmkMk', fn ($query) => $query->where('mata_kuliah_id', $kelas->mata_kuliah_id))
                ->firstOrFail();

            $hasil[$subCpmk->sub_cpmk_id] = $bobot;
        }

        return $hasil;
    }
}
