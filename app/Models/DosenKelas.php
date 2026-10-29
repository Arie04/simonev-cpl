<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot kelas kuliah dan dosen pengampunya.
 *
 * Dibuat sebagai model sendiri (dipasang lewat ->using()) agar PK UUID tetap
 * terisi saat baris dibuat lewat relasi, mis. attach() atau sync().
 */
class DosenKelas extends Pivot
{
    use HasUuids;

    protected $table = 'dosen_kelas';

    protected $primaryKey = 'dosen_kelas_id';

    public function kelasKuliah(): BelongsTo
    {
        return $this->belongsTo(KelasKuliah::class, 'kelas_kuliah_id', 'kelas_kuliah_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_id', 'dosen_id');
    }
}
