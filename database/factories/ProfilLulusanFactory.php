<?php

namespace Database\Factories;

use App\Models\Kurikulum;
use App\Models\ProfilLulusan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfilLulusan>
 */
class ProfilLulusanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kurikulum_id' => Kurikulum::factory(),
            'kode_pl' => fake()->unique()->numerify('PL##'),
            'deskripsi_pl' => fake()->sentence(),
            'unsur' => null,
            'sumber' => null,
        ];
    }
}
