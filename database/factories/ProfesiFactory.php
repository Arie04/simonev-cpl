<?php

namespace Database\Factories;

use App\Models\Kurikulum;
use App\Models\Profesi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profesi>
 */
class ProfesiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kurikulum_id' => Kurikulum::factory(),
            'nama_profesi' => fake()->jobTitle(),
        ];
    }
}
