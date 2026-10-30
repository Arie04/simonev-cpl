<?php

namespace Database\Factories;

use App\Models\EvaluasiKurikulum;
use App\Models\Kurikulum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluasiKurikulum>
 */
class EvaluasiKurikulumFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kurikulum_id' => Kurikulum::factory(),
            'tahap_evaluasi' => 'Evaluasi '.fake()->numberBetween(1, 5),
            'kinerja_mutu' => fake()->sentence(),
            'standar_kinerja_mutu' => fake()->sentence(),
            'kesenjangan' => fake()->sentence(),
            'tindak_lanjut' => fake()->sentence(),
        ];
    }
}
