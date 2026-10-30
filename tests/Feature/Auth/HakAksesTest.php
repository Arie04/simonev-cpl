<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Skenario uji 3 (bagian 12 spesifikasi): membuka fitur di luar kewenangan ditolak.
 * Pembatasan dicek di server lewat middleware 'peran', bukan sekadar menyembunyikan menu.
 */
class HakAksesTest extends TestCase
{
    use RefreshDatabase;

    private const HALAMAN_UTAMA = [
        Role::ADMIN => 'admin.beranda',
        Role::KOPRODI => 'koprodi.dashboard',
        Role::DOSEN => 'dosen.dashboard',
        Role::MAHASISWA => 'mahasiswa.capaian',
    ];

    public static function aksesLintasPeran(): array
    {
        $kasus = [];

        foreach (self::HALAMAN_UTAMA as $kodeRole => $route) {
            foreach (self::HALAMAN_UTAMA as $kodeRoleLain => $routeLain) {
                if ($kodeRole !== $kodeRoleLain) {
                    $kasus["{$kodeRole} membuka halaman {$kodeRoleLain}"] = [$kodeRole, $routeLain];
                }
            }
        }

        return $kasus;
    }

    public static function halamanTerlindungi(): array
    {
        return [
            'beranda' => ['/'],
            'halaman admin' => ['/admin'],
            'profil' => ['/profil'],
            'ubah password' => ['/ubah-password'],
            'pilih peran' => ['/pilih-peran'],
        ];
    }

    #[DataProvider('halamanTerlindungi')]
    public function test_tamu_diarahkan_ke_halaman_login(string $alamat): void
    {
        $this->get($alamat)->assertRedirect(route('login'));
    }

    #[DataProvider('aksesLintasPeran')]
    public function test_pengguna_mendapat_403_saat_membuka_halaman_peran_lain(string $kodeRole, string $routeLain): void
    {
        $user = User::factory()->denganPeran($kodeRole)->create();

        $this->actingAs($user)
            ->withSession(['peran_aktif' => $kodeRole])
            ->get(route($routeLain))
            ->assertForbidden()
            ->assertSee('Anda tidak memiliki akses ke halaman ini.');
    }

    public function test_koprodi_dosen_yang_aktif_sebagai_koprodi_mendapat_403_di_halaman_dosen(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI, Role::DOSEN)->create();

        $this->actingAs($user)
            ->withSession(['peran_aktif' => Role::KOPRODI])
            ->get(route('dosen.dashboard'))
            ->assertForbidden();
    }

    public function test_pengguna_yang_belum_memilih_peran_diarahkan_ke_pilih_peran(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI, Role::DOSEN)->create();

        $this->actingAs($user)
            ->get(route('profil'))
            ->assertRedirect(route('peran-aktif.create'));
    }

    public function test_peran_yang_dicabut_admin_tidak_bisa_dipakai_lagi(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI, Role::DOSEN)->create();
        $user->roles()->detach(Role::where('kode_role', Role::DOSEN)->first());

        $this->actingAs($user)
            ->withSession(['peran_aktif' => Role::DOSEN])
            ->get(route('dosen.dashboard'))
            ->assertRedirect(route('peran-aktif.create'));

        $this->assertNull(session('peran_aktif'));
    }

    public function test_pengguna_yang_semua_perannya_dicabut_dikeluarkan_dari_sistem(): void
    {
        $user = User::factory()->denganPeran(Role::DOSEN)->create();
        $user->roles()->detach();

        $this->actingAs($user)
            ->get(route('peran-aktif.create'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['identitas' => 'Akun Anda belum memiliki peran. Hubungi Admin program studi.']);

        $this->assertGuest();
    }

    public function test_menu_sidebar_hanya_berisi_menu_peran_aktif(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI, Role::DOSEN)->create();

        $this->actingAs($user)
            ->withSession(['peran_aktif' => Role::DOSEN])
            ->get(route('dosen.dashboard'))
            ->assertOk()
            ->assertSee(route('dosen.dashboard'))
            ->assertDontSee(route('koprodi.dashboard'));
    }

    public function test_halaman_yang_tidak_ada_menampilkan_404_berbahasa_indonesia(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan')
            ->assertSee('Kembali ke Beranda');
    }
}
