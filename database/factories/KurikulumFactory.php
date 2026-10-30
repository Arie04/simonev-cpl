<?php

namespace Database\Factories;

use App\Models\Kurikulum;
use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kurikulum>
 */
class KurikulumFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_studi_id' => ProgramStudi::factory(),
            // Tahun acak sengaja lebih lama dari tahun yang ditulis langsung di test (mis. 2021, 2026),
            // supaya kurikulum terbaru di test selalu bisa ditebak.
            'tahun_berlaku' => fake()->unique()->numberBetween(2000, 2019),
            'nama_kurikulum' => fn (array $atribut) => 'Kurikulum '.$atribut['tahun_berlaku'],
        ];
    }
}
