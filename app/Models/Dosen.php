<?php

namespace App\Models;

use Database\Factories\DosenFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dosen extends Model
{
    /** @use HasFactory<DosenFactory> */
    use HasFactory, HasUuids;

    protected $table = 'dosen';

    protected $primaryKey = 'dosen_id';

    protected $fillable = ['user_id', 'nidn', 'nama'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function kelasKuliah(): BelongsToMany
    {
        return $this->belongsToMany(KelasKuliah::class, 'dosen_kelas', 'dosen_id', 'kelas_kuliah_id')
            ->using(DosenKelas::class)
            ->withTimestamps();
    }
}
