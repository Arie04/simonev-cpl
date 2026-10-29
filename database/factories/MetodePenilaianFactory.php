<?php

namespace Database\Factories;

use App\Models\MetodePenilaian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetodePenilaian>
 */
class MetodePenilaianFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode_metode' => fake()->unique()->bothify('MTD-###'),
            'nama_metode' => fake()->randomElement(['Kuis', 'Tugas', 'UTS', 'UAS', 'Proyek', 'Praktikum']),
        ];
    }
}
