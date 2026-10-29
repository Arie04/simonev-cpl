<?php

namespace Database\Factories;

use App\Models\Cpmk;
use App\Models\MataKuliah;
use App\Models\PemetaanCpmkMk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PemetaanCpmkMk>
 */
class PemetaanCpmkMkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cpmk_id' => Cpmk::factory(),
            // MK dibuat di kurikulum yang sama dengan CPMK, karena pemetaan hanya
            // boleh menghubungkan data dari satu kurikulum (bagian 6 spesifikasi).
            'mata_kuliah_id' => fn (array $atribut) => MataKuliah::factory()->state([
                'kurikulum_id' => Cpmk::find($atribut['cpmk_id'])->cpl->kurikulum_id,
            ]),
            'bobot_cpmk' => 50,
        ];
    }
}
