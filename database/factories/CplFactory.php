<?php

namespace Database\Factories;

use App\Models\Cpl;
use App\Models\Kurikulum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cpl>
 */
class CplFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kurikulum_id' => Kurikulum::factory(),
            'kode_cpl' => fake()->unique()->numerify('CPL##'),
            'kategori_cpl' => fake()->randomElement(['Sikap', 'Pengetahuan', 'Keterampilan Umum', 'Keterampilan Khusus']),
            'deskripsi_cpl' => fake()->sentence(),
        ];
    }
}
