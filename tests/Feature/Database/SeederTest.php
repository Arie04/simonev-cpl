<?php

namespace Tests\Feature\Database;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\NilaiAsesmen;
use App\Models\PeriodeAkademik;
use App\Models\Role;
use App\Models\User;
use App\Services\MatriksBobotKelas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Data awal (bagian 10 spesifikasi): peran, akun demo, dan data contoh yang ditambahkan
 * bertahap di setiap iterasi (KD-43).
 */
class SeederTest extends TestCase
{
    use RefreshDatabase;

    /** Lima akun demo utama ditambah 3 dosen dan 23 mahasiswa contoh. */
    private const JUMLAH_AKUN = 31;

    public function test_seeder_membuat_empat_peran_akun_demo_dan_data_contoh(): void
    {
        $this->seed();

        $this->assertSame(
            ['ADMIN', 'DOSEN', 'KOPRODI', 'MAHASISWA'],
            Role::orderBy('kode_role')->pluck('kode_role')->all(),
        );
        $this->assertDatabaseCount('users', self::JUMLAH_AKUN);
        $this->assertDatabaseCount('dosen', 5);
        $this->assertDatabaseCount('mahasiswa', 24);
    }

    public function test_akun_koprodi_dosen_memiliki_dua_peran(): void
    {
        $this->seed();

        $user = User::where('email', 'koprodi.dosen@simonev.test')->first();

        $this->assertTrue($user->punyaPeran(Role::KOPRODI));
        $this->assertTrue($user->punyaPeran(Role::DOSEN));
        $this->assertCount(2, $user->roles);
    }

    public function test_password_akun_demo_adalah_nama_akun_ditambah_123(): void
    {
        $this->seed();

        foreach (['admin', 'koprodi', 'dosen', 'koprodi.dosen', 'mahasiswa'] as $namaAkun) {
            $user = User::where('email', "{$namaAkun}@simonev.test")->first();

            $this->assertTrue(Hash::check("{$namaAkun}123", $user->password), "Password akun {$namaAkun} tidak sesuai.");
        }
    }

    public function test_dosen_dan_mahasiswa_contoh_login_dengan_nidn_atau_npm(): void
    {
        $this->seed();

        $dosen = Dosen::where('nidn', '9990000003')->first();
        $mahasiswa = Mahasiswa::where('nim', '9992600001')->first();

        $this->assertNull($dosen->user->email);
        $this->assertTrue(Hash::check('dosen123', $dosen->user->password));
        $this->assertTrue(Hash::check('mahasiswa123', $mahasiswa->user->password));
        $this->assertSame([2023, 2024, 2026], Mahasiswa::distinct()->orderBy('angkatan')->pluck('angkatan')->all());
    }

    public function test_dua_kurikulum_contoh_memakai_beberapa_kode_yang_sama(): void
    {
        $this->seed();

        $kurikulum2021 = Kurikulum::where('tahun_berlaku', 2021)->first();
        $kurikulum2026 = Kurikulum::where('tahun_berlaku', 2026)->first();

        $this->assertSame($kurikulum2026->kurikulum_id, Kurikulum::terbaru()->kurikulum_id);
        $this->assertTrue($kurikulum2021->cpl()->where('kode_cpl', 'CPL01')->exists());
        $this->assertTrue($kurikulum2026->cpl()->where('kode_cpl', 'CPL01')->exists());
        $this->assertTrue($kurikulum2021->mataKuliah()->where('kode_mata_kuliah', 'INF202')->exists());
        $this->assertTrue($kurikulum2026->mataKuliah()->where('kode_mata_kuliah', 'INF202')->exists());
        $this->assertDatabaseCount('metode_penilaian', 6);
    }

    public function test_bobot_pemetaan_contoh_memenuhi_aturan_5_1(): void
    {
        $this->seed();

        $this->assertDatabaseCount('pemetaan_cpmk_mk', 16);
        $this->assertDatabaseCount('sub_cpmk', 25);

        foreach (MataKuliah::with('pemetaanCpmkMk.subCpmk')->get() as $mataKuliah) {
            $this->assertEqualsWithDelta(100, $mataKuliah->totalBobotCpmk(), 0.001, "Total bobot CPMK {$mataKuliah->kode_mata_kuliah} bukan 100.");

            foreach ($mataKuliah->pemetaanCpmkMk as $pemetaan) {
                $this->assertEqualsWithDelta((float) $pemetaan->bobot_cpmk, $pemetaan->totalBobotSubCpmk(), 0.001, "Bobot Sub-CPMK {$pemetaan->label()} tidak sama dengan bobot CPMK-nya.");
            }
        }
    }

    public function test_kelas_contoh_dari_dua_kurikulum_dengan_peserta_dua_angkatan_lebih(): void
    {
        $this->seed();

        $this->assertDatabaseCount('periode_akademik', 2);
        $this->assertDatabaseCount('kelas_kuliah', 5);
        $this->assertSame('2026/2027 Ganjil', PeriodeAkademik::aktif()->nama());
        $this->assertSame(
            [2023, 2024, 2026],
            Mahasiswa::has('pesertaKelas')->distinct()->orderBy('angkatan')->pluck('angkatan')->all(),
        );

        // Mahasiswa Demo mengikuti kelas dari kurikulum 2021 dan 2026.
        $mahasiswaDemo = Mahasiswa::where('nim', '9992300001')->first();
        $this->assertSame(
            [2021, 2026],
            $mahasiswaDemo->kelasKuliah()->with('mataKuliah.kurikulum')->get()
                ->pluck('mataKuliah.kurikulum.tahun_berlaku')->unique()->sort()->values()->all(),
        );
    }

    public function test_rencana_asesmen_contoh_memenuhi_aturan_5_1(): void
    {
        $this->seed();

        foreach (KelasKuliah::all() as $kelas) {
            $matriks = new MatriksBobotKelas($kelas);
            $namaKelas = $kelas->nama();

            $this->assertNotEmpty($matriks->daftarAsesmen, "Kelas {$namaKelas} belum punya rencana asesmen.");
            $this->assertSame([], $matriks->peringatan(), "Rencana asesmen {$namaKelas} memunculkan peringatan.");

            foreach ($matriks->daftarAsesmen as $asesmen) {
                $this->assertEqualsWithDelta((float) $asesmen->bobot_asesmen, $matriks->totalAsesmen($asesmen), 0.001);
            }
        }
    }

    public function test_nilai_contoh_sesuai_batas_dan_sebagian_sengaja_kosong(): void
    {
        $this->seed();

        // Jumlah nilai = asesmen × peserta per kelas, dikurangi yang sengaja kosong:
        // INF201 4×8, INF202 (UAS belum dinilai) 3×8, INF301 4×8, INF101 4×9, INF102 5×8 − 2.
        $this->assertDatabaseCount('rencana_asesmen', 21);
        $this->assertDatabaseCount('nilai_asesmen', 32 + 24 + 32 + 36 + 38);

        foreach (NilaiAsesmen::with('rencanaAsesmen')->get() as $nilai) {
            $this->assertGreaterThanOrEqual(0, (float) $nilai->nilai);
            $this->assertLessThanOrEqual((float) $nilai->rencanaAsesmen->nilai_maksimum, (float) $nilai->nilai);
        }
    }

    public function test_seeder_bisa_dijalankan_ulang_tanpa_membuat_data_ganda(): void
    {
        $this->seed();
        $jumlahAwal = $this->hitungBaris();

        $this->seed();

        $this->assertSame($jumlahAwal, $this->hitungBaris());
        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('users', self::JUMLAH_AKUN);
    }

    /**
     * Jumlah baris di setiap tabel yang diisi seeder.
     *
     * @return array<string, int>
     */
    private function hitungBaris(): array
    {
        $daftarTabel = [
            'roles', 'users', 'user_roles', 'dosen', 'mahasiswa', 'program_studi', 'kurikulum',
            'evaluasi_kurikulum', 'profil_lulusan', 'profesi', 'profil_lulusan_profesi', 'cpl',
            'bahan_kajian', 'mata_kuliah', 'cpmk', 'metode_penilaian', 'pemetaan_cpl_pl', 'pemetaan_cpl_bk',
            'pemetaan_bk_mk', 'pemetaan_cpmk_mk', 'sub_cpmk', 'sub_cpmk_penilaian', 'periode_akademik',
            'kelas_kuliah', 'dosen_kelas', 'peserta_kelas', 'rencana_asesmen', 'rencana_asesmen_sub_cpmk',
            'nilai_asesmen',
        ];

        return collect($daftarTabel)->mapWithKeys(fn (string $tabel) => [$tabel => DB::table($tabel)->count()])->all();
    }
}
