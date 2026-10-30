<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mahasiswa>
 */
class MahasiswaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->denganPeran(Role::MAHASISWA),
            'nim' => fake()->unique()->numerify('07########'),
            // Nama disimpan sama di users.nama dan mahasiswa.nama (bagian 7.6 spesifikasi).
            'nama' => fn (array $atribut) => User::find($atribut['user_id'])->nama,
            'angkatan' => fake()->numberBetween(2020, 2026),
        ];
    }
}
