<?php

namespace Database\Factories;

use App\Models\NilaiAsesmen;
use App\Models\PesertaKelas;
use App\Models\RencanaAsesmen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NilaiAsesmen>
 */
class NilaiAsesmenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rencana_asesmen_id' => RencanaAsesmen::factory(),
            // Yang bisa dinilai hanya peserta kelas tempat asesmen itu (bagian 6 spesifikasi).
            'peserta_kelas_id' => fn (array $atribut) => PesertaKelas::factory()->state([
                'kelas_kuliah_id' => RencanaAsesmen::find($atribut['rencana_asesmen_id'])->kelas_kuliah_id,
            ]),
            'nilai' => fake()->randomFloat(2, 0, 100),
            'catatan' => null,
        ];
    }
}
