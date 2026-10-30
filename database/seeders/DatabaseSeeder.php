<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi data awal untuk pengembangan lokal (bagian 10 spesifikasi).
     * Data contoh ditambahkan bertahap di iterasi yang membuat fiturnya (KD-43).
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AkunDemoSeeder::class,
            DataMasterDemoSeeder::class,
        ]);
    }
}
