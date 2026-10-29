<?php

namespace Database\Factories;

use App\Models\PeriodeAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodeAkademik>
 */
class PeriodeAkademikFactory extends Factory
{
    public function definition(): array
    {
        $tahunAwal = fake()->numberBetween(2020, 2030);

        return [
            'tahun_akademik' => $tahunAwal.'/'.($tahunAwal + 1),
            'semester_periode' => fake()->randomElement([PeriodeAkademik::GANJIL, PeriodeAkademik::GENAP]),
        ];
    }
}
