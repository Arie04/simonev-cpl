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
        // Tahun acak sengaja lebih lama dari tahun yang ditulis langsung di test (mis. 2026/2027),
        // supaya periode aktif dan periode terbaru di test selalu bisa ditebak.
        $tahunAwal = fake()->numberBetween(2000, 2019);

        return [
            'tahun_akademik' => $tahunAwal.'/'.($tahunAwal + 1),
            'semester_periode' => fake()->randomElement([PeriodeAkademik::GANJIL, PeriodeAkademik::GENAP]),
        ];
    }
}
