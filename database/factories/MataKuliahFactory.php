<?php

namespace Database\Factories;

use App\Models\Kurikulum;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MataKuliah>
 */
class MataKuliahFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kurikulum_id' => Kurikulum::factory(),
            'kode_mata_kuliah' => fake()->unique()->numerify('MK###'),
            'nama_mata_kuliah' => fake()->randomElement([
                'Matematika Diskrit', 'Algoritma', 'Basis Data', 'Struktur Data', 'Pemrograman Web',
            ]),
            'semester' => fake()->numberBetween(1, 8),
            'jumlah_sks' => fake()->numberBetween(2, 4),
            'jenis_mata_kuliah' => fake()->randomElement(['Wajib', 'Pilihan']),
            'level_bloom_mk' => null,
            'keterangan' => null,
        ];
    }
}
