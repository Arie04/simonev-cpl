<?php

namespace Tests\Feature\Database;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_empat_peran_dan_lima_akun_demo(): void
    {
        $this->seed();

        $this->assertSame(
            ['ADMIN', 'DOSEN', 'KOPRODI', 'MAHASISWA'],
            Role::orderBy('kode_role')->pluck('kode_role')->all(),
        );
        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('dosen', 2);
        $this->assertDatabaseCount('mahasiswa', 1);
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

    public function test_seeder_bisa_dijalankan_ulang_tanpa_membuat_data_ganda(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('user_roles', 6);
    }
}
