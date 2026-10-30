<?php

namespace Tests\Feature\Akun;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Profil dan Ubah Password untuk semua peran (bagian 7.6 butir 13 spesifikasi).
 */
class ProfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_profil_dosen_menampilkan_nama_email_nidn_dan_peran(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI, Role::DOSEN)->create([
            'nama' => 'Rina Kartika',
            'email' => 'rina@contoh.test',
        ]);
        Dosen::factory()->for($user)->create(['nidn' => '0011223344']);

        $this->actingAs($user)
            ->withSession(['peran_aktif' => Role::DOSEN])
            ->get(route('profil'))
            ->assertOk()
            ->assertSee('Rina Kartika')
            ->assertSee('rina@contoh.test')
            ->assertSee('NIDN')
            ->assertSee('0011223344')
            ->assertSee('Dosen, Koordinator Program Studi');
    }

    public function test_profil_mahasiswa_tanpa_email_menampilkan_npm_dan_tanda_strip(): void
    {
        $user = User::factory()->tanpaEmail()->denganPeran(Role::MAHASISWA)->create();
        Mahasiswa::factory()->for($user)->create(['nim' => '07352311001']);

        $this->actingAs($user)
            ->withSession(['peran_aktif' => Role::MAHASISWA])
            ->get(route('profil'))
            ->assertOk()
            ->assertSee('NPM')
            ->assertSee('07352311001')
            ->assertSee('–');
    }

    public function test_password_bisa_diubah_dengan_password_lama_yang_benar(): void
    {
        $user = $this->penggunaDosen();

        $this->actingAs($user)
            ->withSession(['peran_aktif' => Role::DOSEN])
            ->put(route('password.update'), [
                'password_lama' => 'password',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertRedirect(route('password.edit'))
            ->assertSessionHas('sukses', 'Password berhasil diubah.');

        $this->assertTrue(Hash::check('password-baru-123', $user->fresh()->password));
    }

    public function test_ubah_password_ditolak_jika_password_lama_salah(): void
    {
        $user = $this->penggunaDosen();

        $this->ubahPassword($user, ['password_lama' => 'bukan-ini', 'password' => 'password-baru-123', 'password_confirmation' => 'password-baru-123'])
            ->assertSessionHasErrors(['password_lama' => 'Password lama salah.']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_baru_minimal_8_karakter(): void
    {
        $this->ubahPassword($this->penggunaDosen(), ['password_lama' => 'password', 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors(['password' => 'Password baru minimal berisi 8 karakter.']);
    }

    public function test_konfirmasi_password_baru_harus_cocok(): void
    {
        $this->ubahPassword($this->penggunaDosen(), ['password_lama' => 'password', 'password' => 'password-baru-123', 'password_confirmation' => 'beda-sendiri'])
            ->assertSessionHasErrors(['password' => 'Konfirmasi password baru tidak cocok.']);
    }

    private function penggunaDosen(): User
    {
        return User::factory()->denganPeran(Role::DOSEN)->create();
    }

    private function ubahPassword(User $user, array $isian)
    {
        return $this->actingAs($user)
            ->withSession(['peran_aktif' => Role::DOSEN])
            ->from(route('password.edit'))
            ->put(route('password.update'), $isian);
    }
}
