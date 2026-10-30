<?php

namespace Tests\Feature\Admin;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeAkademik;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Beranda Admin (bagian 7.6.3 spesifikasi) dan pembatasan halaman Admin (skenario uji 3).
 */
class BerandaAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_menampilkan_jumlah_data_tanpa_data_capaian(): void
    {
        Mahasiswa::factory()->count(3)->create();
        Dosen::factory()->count(2)->create();
        $kurikulumLama = Kurikulum::factory()->create(['tahun_berlaku' => 2021]);
        $kurikulumBaru = Kurikulum::factory()->create(['tahun_berlaku' => 2026, 'nama_kurikulum' => 'Kurikulum 2026']);
        MataKuliah::factory()->count(4)->for($kurikulumBaru)->create();
        MataKuliah::factory()->count(2)->for($kurikulumLama)->create();

        // Periode aktif = periode terbaru yang sudah punya kelas (KD-45), bukan periode yang baru dibuat.
        $periodeAktif = PeriodeAkademik::factory()->create(['tahun_akademik' => '2026/2027', 'semester_periode' => 'Ganjil']);
        PeriodeAkademik::factory()->create(['tahun_akademik' => '2026/2027', 'semester_periode' => 'Genap']);
        KelasKuliah::factory()->count(2)->for($periodeAktif)->create();

        $this->masukSebagai(Role::ADMIN);

        $this->get(route('admin.beranda'))
            ->assertOk()
            ->assertSeeInOrder(['Mahasiswa', '3'])
            ->assertSeeInOrder(['Dosen', '2'])
            ->assertSeeInOrder(['Mata Kuliah', '4', 'Kurikulum 2026'])
            ->assertSeeInOrder(['Kelas', '2', 'Periode 2026/2027 Ganjil'])
            ->assertSee('Pintasan')
            ->assertDontSee('Tercapai');
    }

    public function test_beranda_tetap_tampil_saat_data_masih_kosong(): void
    {
        $this->masukSebagai(Role::ADMIN);

        $this->get(route('admin.beranda'))
            ->assertOk()
            ->assertSee('Belum ada kurikulum')
            ->assertSee('Belum ada kelas');
    }

    public static function halamanAdmin(): array
    {
        return [
            'beranda admin' => ['admin.beranda'],
            'program studi' => ['admin.program-studi.index'],
            'kurikulum' => ['admin.kurikulum.index'],
            'CPL' => ['admin.cpl.index'],
            'dosen' => ['admin.dosen.index'],
            'mahasiswa' => ['admin.mahasiswa.index'],
            'akun pengguna' => ['admin.akun.index'],
        ];
    }

    #[DataProvider('halamanAdmin')]
    public function test_peran_selain_admin_mendapat_403_di_halaman_admin(string $route): void
    {
        foreach ([Role::KOPRODI, Role::DOSEN, Role::MAHASISWA] as $kodeRole) {
            $this->masukSebagai($kodeRole);

            $this->get(route($route))->assertForbidden();
        }
    }

    #[DataProvider('halamanAdmin')]
    public function test_admin_bisa_membuka_halaman_admin(string $route): void
    {
        $this->masukSebagai(Role::ADMIN);

        $this->get(route($route))->assertOk();
    }

    public function test_menu_data_master_terbuka_saat_halamannya_aktif(): void
    {
        $this->masukSebagai(Role::ADMIN);

        $this->get(route('admin.cpl.index'))
            ->assertSee('Data Master')
            ->assertSee('aria-current="page"', false)
            ->assertSeeInOrder(['Program Studi', 'Kurikulum', 'Evaluasi Kurikulum', 'Profil Lulusan', 'Profesi', 'CPL']);
    }
}
