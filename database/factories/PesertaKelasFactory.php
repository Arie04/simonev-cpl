<?php

namespace Database\Factories;

use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use App\Models\PesertaKelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PesertaKelas>
 */
class PesertaKelasFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kelas_kuliah_id' => KelasKuliah::factory(),
            'mahasiswa_id' => Mahasiswa::factory(),
        ];
    }
}
