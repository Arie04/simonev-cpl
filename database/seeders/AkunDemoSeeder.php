<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Akun demo untuk mencoba setiap peran di laptop (bagian 10 spesifikasi).
 * Password-nya bagian email sebelum @ ditambah "123", mis. admin123 (KD-33),
 * jadi hanya untuk pengembangan lokal.
 */
class AkunDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->buatAkun('Admin Program Studi', 'admin@simonev.test', [Role::ADMIN]);
        $this->buatAkun('Koordinator Program Studi', 'koprodi@simonev.test', [Role::KOPRODI]);

        $dosen = $this->buatAkun('Dosen Demo', 'dosen@simonev.test', [Role::DOSEN]);
        Dosen::updateOrCreate(
            ['user_id' => $dosen->user_id],
            ['nidn' => '9990000001', 'nama' => $dosen->nama],
        );

        $koprodiDosen = $this->buatAkun('Koprodi Merangkap Dosen', 'koprodi.dosen@simonev.test', [Role::KOPRODI, Role::DOSEN]);
        Dosen::updateOrCreate(
            ['user_id' => $koprodiDosen->user_id],
            ['nidn' => '9990000002', 'nama' => $koprodiDosen->nama],
        );

        $mahasiswa = $this->buatAkun('Mahasiswa Demo', 'mahasiswa@simonev.test', [Role::MAHASISWA]);
        Mahasiswa::updateOrCreate(
            ['user_id' => $mahasiswa->user_id],
            ['nim' => '9992300001', 'nama' => $mahasiswa->nama, 'angkatan' => 2023],
        );
    }

    /**
     * Membuat (atau memperbarui) satu akun beserta perannya.
     *
     * @param  list<string>  $daftarKodeRole
     */
    private function buatAkun(string $nama, string $email, array $daftarKodeRole): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            ['nama' => $nama, 'password' => Str::before($email, '@').'123'],
        );

        $user->roles()->sync(Role::whereIn('kode_role', $daftarKodeRole)->pluck('role_id'));

        return $user;
    }
}
