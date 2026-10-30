<?php

namespace Database\Factories;

use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramStudi>
 */
class ProgramStudiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_perguruan_tinggi' => 'Universitas Khairun',
            'nama_fakultas' => 'Fakultas Teknik',
            'nama_program_studi' => 'Informatika',
            'izin_pendirian' => null,
            'peringkat_akreditasi' => null,
            'jenjang_pendidikan' => 'Sarjana (S1)',
            'gelar_lulusan' => 'S.Kom.',
            'visi_keilmuan' => fake()->sentence(),
            'misi_program_studi' => fake()->paragraph(),
        ];
    }
}
