<?php

namespace Database\Factories;

use App\Models\BahanKajian;
use App\Models\Kurikulum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BahanKajian>
 */
class BahanKajianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kurikulum_id' => Kurikulum::factory(),
            'kode_bk' => fake()->unique()->numerify('BK##'),
            'nama_bahan_kajian' => fake()->words(3, true),
            'kategori_bk' => fake()->randomElement(['Kompetensi Utama', 'Softskill', 'Umum']),
            'bobot_min' => 2,
            'bobot_max' => 4,
            'cs_core' => fake()->numberBetween(0, 40),
            'ka_core' => fake()->numberBetween(0, 40),
            'referensi' => 'CS2023',
        ];
    }
}
