<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids;

    protected $table = 'users';

    protected $primaryKey = 'user_id';

    protected $fillable = ['nama', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->using(UserRole::class)
            ->withTimestamps();
    }

    public function dosen(): HasOne
    {
        return $this->hasOne(Dosen::class, 'user_id', 'user_id');
    }

    public function mahasiswa(): HasOne
    {
        return $this->hasOne(Mahasiswa::class, 'user_id', 'user_id');
    }

    /**
     * Mencari akun dari isian login: email, lalu NPM (mahasiswa.nim), lalu NIDN (dosen.nidn).
     */
    public static function cariBerdasarkanIdentitas(string $identitas): ?self
    {
        return self::where('email', $identitas)->first()
            ?? Mahasiswa::where('nim', $identitas)->first()?->user
            ?? Dosen::where('nidn', $identitas)->first()?->user;
    }

    /**
     * Mengecek apakah akun ini memiliki peran tertentu, mis. Role::DOSEN.
     */
    public function punyaPeran(string $kodeRole): bool
    {
        return $this->roles->contains('kode_role', $kodeRole);
    }

    /**
     * Inisial nama (maksimal dua huruf) untuk avatar di top bar, mis. "DD" untuk "Dosen Demo".
     */
    public function inisial(): string
    {
        return Str::of($this->nama)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $kata) => Str::upper(Str::substr($kata, 0, 1)))
            ->implode('');
    }
}
