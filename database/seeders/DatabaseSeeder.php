<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi data awal untuk pengembangan lokal (bagian 10 spesifikasi).
     * Data kurikulum, kelas, asesmen, dan nilai ditambahkan di iterasi yang
     * membuat fiturnya.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AkunDemoSeeder::class,
        ]);
    }
}
