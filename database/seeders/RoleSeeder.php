<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Mengisi empat peran tetap: ADMIN, KOPRODI, DOSEN, dan MAHASISWA.
     */
    public function run(): void
    {
        foreach (Role::DAFTAR_PERAN as $kodeRole => $namaRole) {
            Role::updateOrCreate(['kode_role' => $kodeRole], ['nama_role' => $namaRole]);
        }
    }
}
