<?php

namespace Database\Factories;

use App\Models\PemetaanCpmkMk;
use App\Models\SubCpmk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubCpmk>
 */
class SubCpmkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pemetaan_cpmk_mk_id' => PemetaanCpmkMk::factory(),
            'kode_sub_cpmk' => fake()->unique()->numerify('Sub-CPMK####'),
            'deskripsi_sub_cpmk' => fake()->sentence(),
            'bobot_sub_cpmk' => 25,
            'materi' => fake()->sentence(),
            'pertemuan_mulai' => 1,
            'pertemuan_selesai' => 2,
            'catatan' => null,
        ];
    }
}
