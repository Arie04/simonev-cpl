<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot bahan kajian dan mata kuliah, beserta bobot_bk_mk.
 *
 * Dibuat sebagai model sendiri (dipasang lewat ->using()) agar PK UUID tetap
 * terisi saat baris dibuat lewat relasi, mis. attach() atau sync().
 */
class PemetaanBkMk extends Pivot
{
    use HasUuids;

    protected $table = 'pemetaan_bk_mk';

    protected $primaryKey = 'pemetaan_bk_mk_id';

    protected function casts(): array
    {
        return [
            'bobot_bk_mk' => 'decimal:2',
        ];
    }

    public function bahanKajian(): BelongsTo
    {
        return $this->belongsTo(BahanKajian::class, 'bahan_kajian_id', 'bahan_kajian_id');
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id', 'mata_kuliah_id');
    }
}
