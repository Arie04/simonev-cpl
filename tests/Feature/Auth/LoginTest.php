<?php

namespace Tests\Feature\Auth;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skenario uji 1 dan 2 (bagian 12 spesifikasi): login valid dan login salah.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_menampilkan_form_dan_pesan_lupa_password(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Login · SIMONEV-CPL', false)
            ->assertSee('Masuk ke Sistem')
            ->assertSee('Email / NPM / NIDN')
            ->assertSee('Lupa password? Hubungi Admin program studi.');
    }

    public function test_pengguna_bisa_login_dengan_email(): void
    {
        $admin = User::factory()->denganPeran(Role::ADMIN)->create(['email' => 'admin@contoh.test']);

        $this->post('/login', ['identitas' => 'admin@contoh.test', 'password' => 'password'])
            ->assertRedirect(route('peran-aktif.create'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_dosen_bisa_login_dengan_nidn(): void
    {
        $dosen = Dosen::factory()->create(['nidn' => '0011223344']);

        $this->post('/login', ['identitas' => '0011223344', 'password' => 'password'])
            ->assertRedirect(route('peran-aktif.create'));

        $this->assertAuthenticatedAs($dosen->user);
    }

    public function test_mahasiswa_bisa_login_dengan_npm(): void
    {
        $mahasiswa = Mahasiswa::factory()->create(['nim' => '07352311001']);

        $this->post('/login', ['identitas' => '07352311001', 'password' => 'password'])
            ->assertRedirect(route('peran-aktif.create'));

        $this->assertAuthenticatedAs($mahasiswa->user);
    }

    public function test_mahasiswa_tanpa_email_tetap_bisa_login_dengan_npm(): void
    {
        $user = User::factory()->tanpaEmail()->denganPeran(Role::MAHASISWA)->create();
        Mahasiswa::factory()->for($user)->create(['nim' => '07352311002']);

        $this->post('/login', ['identitas' => '07352311002', 'password' => 'password'])
            ->assertRedirect(route('peran-aktif.create'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_dengan_password_salah_ditolak_dengan_pesan(): void
    {
        User::factory()->denganPeran(Role::ADMIN)->create(['email' => 'admin@contoh.test']);

        $this->from('/login')
            ->post('/login', ['identitas' => 'admin@contoh.test', 'password' => 'salah-sekali'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['identitas' => 'Email/NPM/NIDN atau password salah.']);

        $this->assertGuest();
    }

    public function test_login_dengan_identitas_yang_tidak_terdaftar_ditolak_dengan_pesan(): void
    {
        $this->from('/login')
            ->post('/login', ['identitas' => 'tidak.ada@contoh.test', 'password' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['identitas' => 'Email/NPM/NIDN atau password salah.']);

        $this->assertGuest();
    }

    public function test_identitas_dan_password_wajib_diisi(): void
    {
        $this->post('/login', [])
            ->assertSessionHasErrors([
                'identitas' => 'Email / NPM / NIDN wajib diisi.',
                'password' => 'Password wajib diisi.',
            ]);
    }

    public function test_login_diblokir_setelah_lima_kali_gagal_dalam_satu_menit(): void
    {
        User::factory()->denganPeran(Role::ADMIN)->create(['email' => 'admin@contoh.test']);

        for ($percobaan = 1; $percobaan <= 5; $percobaan++) {
            $this->post('/login', ['identitas' => 'admin@contoh.test', 'password' => 'salah-sekali']);
        }

        // Percobaan ke-6 tetap ditolak walaupun password-nya benar.
        $this->post('/login', ['identitas' => 'admin@contoh.test', 'password' => 'password'])
            ->assertSessionHasErrors('identitas');

        $this->assertGuest();
        $this->assertStringStartsWith(
            'Terlalu banyak percobaan login. Silakan coba lagi dalam',
            session('errors')->first('identitas'),
        );
    }

    public function test_akun_tanpa_peran_tidak_bisa_login(): void
    {
        User::factory()->create(['email' => 'tanpa.peran@contoh.test']);

        $this->post('/login', ['identitas' => 'tanpa.peran@contoh.test', 'password' => 'password'])
            ->assertSessionHasErrors(['identitas' => 'Akun Anda belum memiliki peran. Hubungi Admin program studi.']);

        $this->assertGuest();
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $admin = User::factory()->denganPeran(Role::ADMIN)->create();

        $this->actingAs($admin)
            ->withSession(['peran_aktif' => Role::ADMIN])
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_pengguna_yang_sudah_login_diarahkan_ke_beranda_saat_membuka_halaman_login(): void
    {
        $admin = User::factory()->denganPeran(Role::ADMIN)->create();

        $this->actingAs($admin)->get('/login')->assertRedirect(route('beranda'));
    }
}
