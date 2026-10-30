<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot CPL dan bahan kajian.
 *
 * Dibuat sebagai model sendiri (dipasang lewat ->using()) agar PK UUID tetap
 * terisi saat baris dibuat lewat relasi, mis. attach() atau sync().
 */
class PemetaanCplBk extends Pivot
{
    use HasUuids;

    protected $table = 'pemetaan_cpl_bk';

    protected $primaryKey = 'pemetaan_cpl_bk_id';

    public function cpl(): BelongsTo
    {
        return $this->belongsTo(Cpl::class, 'cpl_id', 'cpl_id');
    }

    public function bahanKajian(): BelongsTo
    {
        return $this->belongsTo(BahanKajian::class, 'bahan_kajian_id', 'bahan_kajian_id');
    }
}
