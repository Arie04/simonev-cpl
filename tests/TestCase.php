<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Membuat aplikasi untuk test, lalu memastikan database yang dipakai
     * adalah database test.
     *
     * Test mengosongkan seluruh isi database (RefreshDatabase). Jika config
     * sedang di-cache, pengaturan database test di phpunit.xml bisa terlewati,
     * jadi pengecekan ini mencegah database pengembangan ikut terhapus (KD-17).
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $koneksi = $app['config']->get('database.default');
        $namaDatabase = (string) $app['config']->get("database.connections.{$koneksi}.database");

        if (! str_ends_with($namaDatabase, '_test')) {
            throw new RuntimeException(
                "Test dihentikan: database '{$namaDatabase}' bukan database test. "
                .'Jalankan `php artisan config:clear`, lalu periksa DB_DATABASE di phpunit.xml.'
            );
        }

        return $app;
    }

    /**
     * Login sebagai pengguna dengan peran aktif tertentu, mis. masukSebagai(Role::ADMIN).
     * Jika $user tidak diberikan, dibuat akun baru dengan peran itu.
     */
    protected function masukSebagai(string $kodeRole, ?User $user = null): User
    {
        $user ??= User::factory()->denganPeran($kodeRole)->create();

        $this->actingAs($user)->withSession(['peran_aktif' => $kodeRole]);

        return $user;
    }
}
