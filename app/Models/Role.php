<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasUuids;

    public const ADMIN = 'ADMIN';

    public const KOPRODI = 'KOPRODI';

    public const DOSEN = 'DOSEN';

    public const MAHASISWA = 'MAHASISWA';

    // Peran bersifat tetap (bagian 3 spesifikasi), jadi diisi seeder, bukan dari form.
    public const DAFTAR_PERAN = [
        self::ADMIN => 'Admin',
        self::KOPRODI => 'Koordinator Program Studi',
        self::DOSEN => 'Dosen',
        self::MAHASISWA => 'Mahasiswa',
    ];

    protected $table = 'roles';

    protected $primaryKey = 'role_id';

    protected $fillable = ['kode_role', 'nama_role'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
            ->using(UserRole::class)
            ->withTimestamps();
    }

    /**
     * Mengambil peran berdasarkan kodenya. Peran bersifat tetap (bagian 10), jadi dibuat
     * jika belum ada, mis. di database test yang belum menjalankan seeder.
     */
    public static function ambil(string $kodeRole): self
    {
        return self::firstOrCreate(
            ['kode_role' => $kodeRole],
            ['nama_role' => self::DAFTAR_PERAN[$kodeRole]],
        );
    }
}
