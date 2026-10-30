<?php

namespace Database\Factories;

use App\Models\KelasKuliah;
use App\Models\MetodePenilaian;
use App\Models\RencanaAsesmen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RencanaAsesmen>
 */
class RencanaAsesmenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kelas_kuliah_id' => KelasKuliah::factory(),
            'metode_penilaian_id' => MetodePenilaian::factory(),
            'nama_asesmen' => fake()->randomElement(['Kuis 1', 'Tugas 1', 'UTS', 'UAS']),
            'bobot_asesmen' => 25,
            'nilai_maksimum' => 100,
            'tanggal_asesmen' => fake()->dateTimeBetween('-3 months', '+3 months')->format('Y-m-d'),
        ];
    }
}
