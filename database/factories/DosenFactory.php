<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dosen>
 */
class DosenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->denganPeran(Role::DOSEN),
            'nidn' => fake()->unique()->numerify('00########'),
            // Nama disimpan sama di users.nama dan dosen.nama (bagian 7.6 spesifikasi).
            'nama' => fn (array $atribut) => User::find($atribut['user_id'])->nama,
        ];
    }
}
