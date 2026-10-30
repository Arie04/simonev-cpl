<?php

namespace Database\Factories;

use App\Models\KelasKuliah;
use App\Models\MataKuliah;
use App\Models\PeriodeAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KelasKuliah>
 */
class KelasKuliahFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mata_kuliah_id' => MataKuliah::factory(),
            'periode_akademik_id' => PeriodeAkademik::factory(),
            'nama_kelas' => fake()->randomElement(['A', 'B', 'C']),
        ];
    }
}
