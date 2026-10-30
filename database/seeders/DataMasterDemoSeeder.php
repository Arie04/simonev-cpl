<?php

namespace Database\Seeders;

use App\Models\BahanKajian;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Dosen;
use App\Models\EvaluasiKurikulum;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\MetodePenilaian;
use App\Models\Profesi;
use App\Models\ProfilLulusan;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh data master (bagian 10 spesifikasi, KD-43): satu program studi, kurikulum 2021
 * (lama) dan 2026 (baru) dengan beberapa kode yang sama di keduanya, serta dosen dan mahasiswa
 * contoh. Semua isinya karangan untuk pengembangan lokal, bukan data asli prodi.
 *
 * Dosen dan mahasiswa contoh tidak punya email; mereka login dengan NIDN/NPM dan password
 * dosen123 atau mahasiswa123.
 */
class DataMasterDemoSeeder extends Seeder
{
    public function run(): void
    {
        $programStudi = ProgramStudi::updateOrCreate(
            ['nama_program_studi' => 'Informatika'],
            [
                'nama_perguruan_tinggi' => 'Universitas Khairun',
                'nama_fakultas' => 'Fakultas Teknik',
                'jenjang_pendidikan' => 'S1',
                'gelar_lulusan' => 'S.Kom.',
                'peringkat_akreditasi' => 'Baik Sekali',
                'visi_keilmuan' => 'Menjadi program studi informatika yang unggul dalam pengembangan teknologi informasi berbasis kemaritiman.',
                'misi_program_studi' => 'Menyelenggarakan pendidikan, penelitian, dan pengabdian kepada masyarakat di bidang informatika.',
            ],
        );

        $kurikulum2021 = Kurikulum::updateOrCreate(
            ['tahun_berlaku' => 2021],
            ['program_studi_id' => $programStudi->program_studi_id, 'nama_kurikulum' => 'Kurikulum 2021'],
        );
        $kurikulum2026 = Kurikulum::updateOrCreate(
            ['tahun_berlaku' => 2026],
            ['program_studi_id' => $programStudi->program_studi_id, 'nama_kurikulum' => 'Kurikulum OBE 2026'],
        );

        $this->isiEvaluasiKurikulum($kurikulum2026);
        $this->isiKurikulum2021($kurikulum2021);
        $this->isiKurikulum2026($kurikulum2026);
        $this->isiMetodePenilaian();
        $this->isiDosenDanMahasiswa();
    }

    private function isiEvaluasiKurikulum(Kurikulum $kurikulum): void
    {
        $daftarEvaluasi = [
            ['Evaluasi kurikulum 2021', 'Rata-rata masa studi 4,6 tahun', 'Masa studi maksimal 4 tahun', 'Masa studi 0,6 tahun lebih lama dari standar', 'Menyederhanakan prasyarat mata kuliah'],
            ['Evaluasi kurikulum 2021', 'Keterserapan lulusan dalam 6 bulan 62%', 'Keterserapan minimal 70%', 'Keterserapan 8% di bawah standar', 'Menambah mata kuliah proyek dan magang'],
            ['Masukan pengguna lulusan', 'Kemampuan basis data dinilai cukup', 'Kemampuan basis data dinilai baik', 'Praktik basis data masih kurang', 'Menambah porsi praktikum basis data'],
        ];

        foreach ($daftarEvaluasi as [$tahap, $kinerja, $standar, $kesenjangan, $tindakLanjut]) {
            EvaluasiKurikulum::updateOrCreate(
                ['kurikulum_id' => $kurikulum->kurikulum_id, 'kinerja_mutu' => $kinerja],
                [
                    'tahap_evaluasi' => $tahap,
                    'standar_kinerja_mutu' => $standar,
                    'kesenjangan' => $kesenjangan,
                    'tindak_lanjut' => $tindakLanjut,
                ],
            );
        }
    }

    private function isiKurikulum2021(Kurikulum $kurikulum): void
    {
        $this->isiProfilLulusanDanProfesi($kurikulum, [
            'PL01' => ['Pengembang perangkat lunak yang mampu membangun aplikasi web dan desktop.', ['Software Engineer', 'Web Developer']],
            'PL02' => ['Pengelola basis data dan infrastruktur sistem informasi.', ['Database Administrator']],
            'PL03' => ['Akademisi atau peneliti di bidang informatika.', ['Peneliti']],
        ]);

        $this->isiCplDanCpmk($kurikulum, [
            'CPL01' => ['Sikap', 'Menginternalisasi nilai, norma, dan etika akademik dalam bekerja.', [
                'CPMK011' => 'Mampu menerapkan etika profesi dalam pengembangan perangkat lunak.',
                'CPMK012' => 'Mampu bertanggung jawab atas tugas secara mandiri.',
            ]],
            'CPL02' => ['Pengetahuan', 'Menguasai konsep teoretis struktur data dan basis data.', [
                'CPMK021' => 'Mampu menjelaskan struktur data linear dan nonlinear.',
                'CPMK022' => 'Mampu menjelaskan model data relasional dan normalisasi.',
            ]],
            'CPL03' => ['Keterampilan Khusus', 'Mampu membangun aplikasi berbasis web yang aman dan mudah digunakan.', [
                'CPMK031' => 'Mampu merancang antarmuka web yang responsif.',
                'CPMK032' => 'Mampu mengimplementasikan aplikasi web dengan basis data.',
            ]],
            'CPL04' => ['Keterampilan Umum', 'Mampu bekerja sama dan berkomunikasi secara efektif dalam tim.', [
                'CPMK041' => 'Mampu mempresentasikan hasil proyek secara lisan dan tertulis.',
                'CPMK042' => 'Mampu bekerja dalam tim pengembang.',
            ]],
        ]);

        $this->isiBahanKajian($kurikulum, [
            ['BK01', 'Algoritma dan Struktur Data', 'Kompetensi Utama', 3, 5, 17, 14, 'CS2023'],
            ['BK02', 'Manajemen Data', 'Kompetensi Utama', 3, 5, 10, 26, 'CS2023'],
            ['BK03', 'Pengembangan Berbasis Platform', 'Kompetensi Utama', 2, 4, 0, 6, 'CS2023'],
            ['BK04', 'Etika Profesi', 'Softskill', 2, 3, null, null, 'CC2020'],
        ]);

        $this->isiMataKuliah($kurikulum, [
            ['INF201', 'Struktur Data', 3, 3, 'Wajib', 'C3'],
            ['INF202', 'Basis Data', 3, 3, 'Wajib', 'C4'],
            ['INF301', 'Pemrograman Web', 5, 3, 'Wajib', 'C5'],
        ]);
    }

    private function isiKurikulum2026(Kurikulum $kurikulum): void
    {
        // PL01, CPL01, CPMK011, BK01, dan MK INF202 sengaja memakai kode yang sama dengan
        // kurikulum 2021 (bagian 10): datanya tetap terpisah karena dibedakan lewat id.
        $this->isiProfilLulusanDanProfesi($kurikulum, [
            'PL01' => ['Pengembang perangkat lunak yang menerapkan rekayasa perangkat lunak modern.', ['Software Engineer', 'Mobile Developer']],
            'PL02' => ['Analis data yang mampu mengolah data menjadi informasi untuk pengambilan keputusan.', ['Data Analyst']],
            'PL03' => ['Technopreneur di bidang teknologi informasi.', ['IT Consultant']],
        ]);

        $this->isiCplDanCpmk($kurikulum, [
            'CPL01' => ['Sikap', 'Menunjukkan sikap bertanggung jawab atas pekerjaan di bidang keahliannya secara mandiri.', [
                'CPMK011' => 'Mampu menyelesaikan tugas secara jujur dan tepat waktu.',
                'CPMK012' => 'Mampu menerapkan etika dalam penggunaan teknologi informasi.',
            ]],
            'CPL02' => ['Pengetahuan', 'Menguasai konsep teoretis matematika diskrit sebagai dasar ilmu komputer.', [
                'CPMK021' => 'Mampu menjelaskan logika proposisi dan teknik pembuktian.',
                'CPMK022' => 'Mampu menjelaskan himpunan, relasi, fungsi, dan kombinatorika.',
            ]],
            'CPL03' => ['Keterampilan Umum', 'Mampu menerapkan pemikiran logis, kritis, dan sistematis dalam pengembangan ilmu pengetahuan dan teknologi.', [
                'CPMK031' => 'Mampu memodelkan masalah dengan graf dan pohon.',
                'CPMK032' => 'Mampu menganalisis kebutuhan data sebuah organisasi.',
            ]],
            'CPL04' => ['Keterampilan Khusus', 'Mampu merancang dan mengimplementasikan algoritma untuk menyelesaikan masalah komputasi.', [
                'CPMK041' => 'Mampu menyusun algoritma dengan struktur kontrol dan fungsi.',
                'CPMK042' => 'Mampu mengimplementasikan algoritma dalam bahasa pemrograman.',
            ]],
            'CPL05' => ['Keterampilan Khusus', 'Mampu merancang dan mengelola basis data untuk mendukung kebutuhan sistem informasi.', [
                'CPMK051' => 'Mampu merancang basis data relasional yang ternormalisasi.',
                'CPMK052' => 'Mampu menulis kueri SQL untuk mengelola data.',
            ]],
        ]);

        $this->isiBahanKajian($kurikulum, [
            ['BK01', 'Algoritma dan Pemrograman', 'Kompetensi Utama', 3, 6, 17, 14, 'CS2023'],
            ['BK02', 'Struktur Diskrit', 'Kompetensi Utama', 3, 5, 18, 4, 'CS2023'],
            ['BK03', 'Manajemen Data', 'Kompetensi Utama', 3, 5, 10, 26, 'CS2023'],
            ['BK04', 'Kewirausahaan Teknologi', 'Softskill', 2, 3, null, null, 'CC2020'],
            ['BK05', 'Pendidikan Agama dan Pancasila', 'Umum', null, null, null, null, null],
        ]);

        $this->isiMataKuliah($kurikulum, [
            ['INF101', 'Matematika Diskrit', 1, 3, 'Wajib', 'C3'],
            ['INF102', 'Algoritma dan Pemrograman', 1, 4, 'Wajib', 'C3'],
            ['INF202', 'Basis Data', 3, 3, 'Wajib', 'C4'],
        ]);
    }

    /**
     * @param  array<string, array{0: string, 1: list<string>}>  $daftarProfilLulusan  kode => [deskripsi, daftar profesi]
     */
    private function isiProfilLulusanDanProfesi(Kurikulum $kurikulum, array $daftarProfilLulusan): void
    {
        foreach ($daftarProfilLulusan as $kodePl => [$deskripsi, $daftarNamaProfesi]) {
            $profilLulusan = ProfilLulusan::updateOrCreate(
                ['kurikulum_id' => $kurikulum->kurikulum_id, 'kode_pl' => $kodePl],
                ['deskripsi_pl' => $deskripsi, 'unsur' => 'Informatika', 'sumber' => 'Studi pelacakan dan asosiasi profesi'],
            );

            $daftarIdProfesi = collect($daftarNamaProfesi)->map(fn (string $namaProfesi) => Profesi::updateOrCreate(
                ['kurikulum_id' => $kurikulum->kurikulum_id, 'nama_profesi' => $namaProfesi],
            )->profesi_id);

            $profilLulusan->profesi()->sync($daftarIdProfesi);
        }
    }

    /**
     * @param  array<string, array{0: string, 1: string, 2: array<string, string>}>  $daftarCpl  kode => [kategori, deskripsi, CPMK]
     */
    private function isiCplDanCpmk(Kurikulum $kurikulum, array $daftarCpl): void
    {
        foreach ($daftarCpl as $kodeCpl => [$kategori, $deskripsi, $daftarCpmk]) {
            $cpl = Cpl::updateOrCreate(
                ['kurikulum_id' => $kurikulum->kurikulum_id, 'kode_cpl' => $kodeCpl],
                ['kategori_cpl' => $kategori, 'deskripsi_cpl' => $deskripsi],
            );

            foreach ($daftarCpmk as $kodeCpmk => $deskripsiCpmk) {
                Cpmk::updateOrCreate(
                    ['cpl_id' => $cpl->cpl_id, 'kode_cpmk' => $kodeCpmk],
                    ['deskripsi_cpmk' => $deskripsiCpmk],
                );
            }
        }
    }

    private function isiBahanKajian(Kurikulum $kurikulum, array $daftarBahanKajian): void
    {
        foreach ($daftarBahanKajian as [$kode, $nama, $kategori, $bobotMin, $bobotMax, $csCore, $kaCore, $referensi]) {
            BahanKajian::updateOrCreate(
                ['kurikulum_id' => $kurikulum->kurikulum_id, 'kode_bk' => $kode],
                [
                    'nama_bahan_kajian' => $nama,
                    'kategori_bk' => $kategori,
                    'bobot_min' => $bobotMin,
                    'bobot_max' => $bobotMax,
                    'cs_core' => $csCore,
                    'ka_core' => $kaCore,
                    'referensi' => $referensi,
                ],
            );
        }
    }

    private function isiMataKuliah(Kurikulum $kurikulum, array $daftarMataKuliah): void
    {
        foreach ($daftarMataKuliah as [$kode, $nama, $semester, $sks, $jenis, $levelBloom]) {
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
    }

    private function isiMetodePenilaian(): void
    {
        $daftarMetode = [
            'KUIS' => 'Kuis',
            'TUGAS' => 'Tugas',
            'UTS' => 'Ujian Tengah Semester',
            'UAS' => 'Ujian Akhir Semester',
            'PROYEK' => 'Proyek',
            'PRAKTIKUM' => 'Praktikum',
        ];

        foreach ($daftarMetode as $kode => $nama) {
            MetodePenilaian::updateOrCreate(['kode_metode' => $kode], ['nama_metode' => $nama]);
        }
    }

    /**
     * Tiga dosen dan 23 mahasiswa contoh dari tiga angkatan (2023, 2024, 2026), tanpa email.
     * Bersama Mahasiswa Demo (angkatan 2023), setiap angkatan berisi 8 mahasiswa.
     */
    private function isiDosenDanMahasiswa(): void
    {
        $daftarDosen = [
            '9990000003' => 'Fadli Ramadhan, M.Kom.',
            '9990000004' => 'Nurul Hidayah, M.T.',
            '9990000005' => 'Ahmad Syarif, M.Cs.',
        ];

        foreach ($daftarDosen as $nidn => $nama) {
            $user = $this->buatAkunTanpaEmail($nama, 'dosen123', Role::DOSEN, Dosen::where('nidn', $nidn)->first()?->user);
            Dosen::updateOrCreate(['nidn' => $nidn], ['user_id' => $user->user_id, 'nama' => $nama]);
        }

        $daftarNama = [
            2023 => ['Aisyah Putri', 'Bagas Pratama', 'Citra Lestari', 'Dimas Saputra', 'Eka Wulandari', 'Fajar Nugroho', 'Gita Maharani'],
            2024 => ['Hendra Kurniawan', 'Indah Permata', 'Joko Susilo', 'Kartika Sari', 'Lukman Hakim', 'Maya Anggraini', 'Nanda Firmansyah', 'Oktavia Rahma'],
            2026 => ['Putra Wijaya', 'Qori Amalia', 'Rizky Ananda', 'Salsabila Zahra', 'Taufik Hidayat', 'Umi Kalsum', 'Vino Prasetyo', 'Wulan Safitri'],
        ];

        foreach ($daftarNama as $angkatan => $daftarNamaMahasiswa) {
            foreach ($daftarNamaMahasiswa as $indeks => $nama) {
                // NPM contoh: 999 + dua digit angkatan + nomor urut; nomor 001 angkatan 2023 milik Mahasiswa Demo.
                $nomorUrut = $angkatan === 2023 ? $indeks + 2 : $indeks + 1;
                $nim = '999'.substr((string) $angkatan, 2).sprintf('%05d', $nomorUrut);

                $user = $this->buatAkunTanpaEmail($nama, 'mahasiswa123', Role::MAHASISWA, Mahasiswa::where('nim', $nim)->first()?->user);
                Mahasiswa::updateOrCreate(['nim' => $nim], ['user_id' => $user->user_id, 'nama' => $nama, 'angkatan' => $angkatan]);
            }
        }
    }

    /**
     * Akun dosen/mahasiswa contoh tanpa email. Jika akunnya sudah ada (seeder dijalankan ulang),
     * akun itu yang diperbarui.
     */
    private function buatAkunTanpaEmail(string $nama, string $password, string $kodeRole, ?User $user): User
    {
        $user ??= new User;
        $user->fill(['nama' => $nama, 'email' => null, 'password' => $password])->save();
        $user->roles()->sync([Role::ambil($kodeRole)->role_id]);

        return $user;
    }
}
