<?php

namespace App\Models;

use Database\Factories\MahasiswaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mahasiswa extends Model
{
    /** @use HasFactory<MahasiswaFactory> */
    use HasFactory, HasUuids;

    protected $table = 'mahasiswa';

    protected $primaryKey = 'mahasiswa_id';

    protected $fillable = ['user_id', 'nim', 'nama', 'angkatan'];

    protected function casts(): array
    {
        return [
            'angkatan' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function pesertaKelas(): HasMany
    {
        return $this->hasMany(PesertaKelas::class, 'mahasiswa_id', 'mahasiswa_id');
    }

    public function kelasKuliah(): BelongsToMany
    {
        return $this->belongsToMany(KelasKuliah::class, 'peserta_kelas', 'mahasiswa_id', 'kelas_kuliah_id')
            ->using(PesertaKelas::class)
            ->withPivot('peserta_kelas_id')
            ->withTimestamps();
    }
}
