<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    // Hash password dibuat sekali lalu dipakai ulang agar test tidak lambat.
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Akun tanpa email, seperti dosen/mahasiswa yang login dengan NIDN/NPM (KD-19).
     */
    public function tanpaEmail(): static
    {
        return $this->state(fn () => ['email' => null]);
    }

    /**
     * Memberi akun satu atau beberapa peran, mis. denganPeran(Role::KOPRODI, Role::DOSEN).
     */
    public function denganPeran(string ...$daftarKodeRole): static
    {
        return $this->afterCreating(function (User $user) use ($daftarKodeRole) {
            foreach ($daftarKodeRole as $kodeRole) {
                $role = Role::firstOrCreate(
                    ['kode_role' => $kodeRole],
                    ['nama_role' => Role::DAFTAR_PERAN[$kodeRole]],
                );

                $user->roles()->attach($role);
            }
        });
    }
}
