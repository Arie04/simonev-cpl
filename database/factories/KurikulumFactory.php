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
            'tahun_berlaku' => fake()->unique()->numberBetween(2000, 2099),
            'nama_kurikulum' => fn (array $atribut) => 'Kurikulum '.$atribut['tahun_berlaku'],
        ];
    }
}
