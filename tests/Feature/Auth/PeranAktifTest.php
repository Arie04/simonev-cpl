<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pilih dan ganti peran aktif setelah login (bagian 3 spesifikasi, KD-26).
 */
class PeranAktifTest extends TestCase
{
    use RefreshDatabase;

    public static function peranDanHalamanUtama(): array
    {
        return [
            'Admin' => [Role::ADMIN, 'admin.beranda'],
            'Koprodi' => [Role::KOPRODI, 'koprodi.dashboard'],
            'Dosen' => [Role::DOSEN, 'dosen.dashboard'],
            'Mahasiswa' => [Role::MAHASISWA, 'mahasiswa.capaian'],
        ];
    }

    #[DataProvider('peranDanHalamanUtama')]
    public function test_pengguna_berperan_satu_langsung_diarahkan_ke_halaman_utama_perannya(string $kodeRole, string $routeHalamanUtama): void
    {
        User::factory()->denganPeran($kodeRole)->create(['email' => 'pengguna@contoh.test']);

        $this->post('/login', ['identitas' => 'pengguna@contoh.test', 'password' => 'password'])
            ->assertRedirect(route('peran-aktif.create'));
        $this->get(route('peran-aktif.create'))->assertRedirect(route('beranda'));
        $this->get(route('beranda'))->assertRedirect(route($routeHalamanUtama));

        // Nama peran aktif tampil di top bar.
        $this->get(route($routeHalamanUtama))
            ->assertOk()
            ->assertSee(Role::DAFTAR_PERAN[$kodeRole]);
        $this->assertSame($kodeRole, session('peran_aktif'));
    }

    public function test_pengguna_berperan_dua_diminta_memilih_peran(): void
    {
        User::factory()->denganPeran(Role::KOPRODI, Role::DOSEN)->create(['email' => 'kaprodi@contoh.test']);

        $this->post('/login', ['identitas' => 'kaprodi@contoh.test', 'password' => 'password']);

        $this->get(route('peran-aktif.create'))
            ->assertOk()
            ->assertSee('Pilih Peran')
            ->assertSee('Koordinator Program Studi')
            ->assertSee('Dosen');
        $this->assertNull(session('peran_aktif'));
    }

    public function test_pengguna_bisa_memilih_peran_aktif(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI, Role::DOSEN)->create();
        $roleDosen = Role::where('kode_role', Role::DOSEN)->first();

        $this->actingAs($user)
            ->post(route('peran-aktif.store'), ['role_id' => $roleDosen->role_id])
            ->assertRedirect(route('beranda'))
            ->assertSessionHas('sukses', 'Anda masuk sebagai Dosen.');

        $this->assertSame(Role::DOSEN, session('peran_aktif'));
        $this->get(route('beranda'))->assertRedirect(route('dosen.dashboard'));
    }

    public function test_pengguna_tidak_bisa_memilih_peran_yang_tidak_dimiliki(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI, Role::DOSEN)->create();
        User::factory()->denganPeran(Role::ADMIN)->create();
        $roleAdmin = Role::where('kode_role', Role::ADMIN)->first();

        $this->actingAs($user)
            ->post(route('peran-aktif.store'), ['role_id' => $roleAdmin->role_id])
            ->assertSessionHasErrors(['role_id' => 'Peran yang dipilih tidak valid.']);

        $this->assertNull(session('peran_aktif'));
    }

    public function test_isian_peran_harus_berupa_uuid(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI, Role::DOSEN)->create();

        $this->actingAs($user)
            ->post(route('peran-aktif.store'), ['role_id' => 'DOSEN'])
            ->assertSessionHasErrors(['role_id' => 'Peran harus berupa UUID yang valid.']);
    }

    public function test_pengguna_bisa_berganti_peran_lewat_menu_akun(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI, Role::DOSEN)->create();
        $roleDosen = Role::where('kode_role', Role::DOSEN)->first();

        $this->actingAs($user)
            ->withSession(['peran_aktif' => Role::KOPRODI])
            ->get(route('peran-aktif.create'))
            ->assertOk()
            ->assertSee('Sedang aktif')
            ->assertSee('Batal');

        $this->post(route('peran-aktif.store'), ['role_id' => $roleDosen->role_id]);

        $this->assertSame(Role::DOSEN, session('peran_aktif'));
    }

    public function test_menu_ganti_peran_hanya_tampil_untuk_pengguna_berperan_lebih_dari_satu(): void
    {
        $admin = User::factory()->denganPeran(Role::ADMIN)->create();
        $koprodiDosen = User::factory()->denganPeran(Role::KOPRODI, Role::DOSEN)->create();

        $this->actingAs($admin)
            ->withSession(['peran_aktif' => Role::ADMIN])
            ->get(route('admin.beranda'))
            ->assertOk()
            ->assertDontSee('Ganti Peran');

        $this->actingAs($koprodiDosen)
            ->withSession(['peran_aktif' => Role::KOPRODI])
            ->get(route('koprodi.dashboard'))
            ->assertOk()
            ->assertSee('Ganti Peran');
    }
}
