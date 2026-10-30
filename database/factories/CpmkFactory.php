<?php

namespace Database\Factories;

use App\Models\Cpl;
use App\Models\Cpmk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cpmk>
 */
class CpmkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cpl_id' => Cpl::factory(),
            'kode_cpmk' => fake()->unique()->numerify('CPMK###'),
            'deskripsi_cpmk' => fake()->sentence(),
        ];
    }
}
