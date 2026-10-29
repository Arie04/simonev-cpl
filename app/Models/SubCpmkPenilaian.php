<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot Sub-CPMK dan metode penilaian yang direncanakan.
 *
 * Dibuat sebagai model sendiri (dipasang lewat ->using()) agar PK UUID tetap
 * terisi saat baris dibuat lewat relasi, mis. attach() atau sync().
 */
class SubCpmkPenilaian extends Pivot
{
    use HasUuids;

    protected $table = 'sub_cpmk_penilaian';

    protected $primaryKey = 'sub_cpmk_penilaian_id';

    public function subCpmk(): BelongsTo
    {
        return $this->belongsTo(SubCpmk::class, 'sub_cpmk_id', 'sub_cpmk_id');
    }

    public function metodePenilaian(): BelongsTo
    {
        return $this->belongsTo(MetodePenilaian::class, 'metode_penilaian_id', 'metode_penilaian_id');
    }
}
