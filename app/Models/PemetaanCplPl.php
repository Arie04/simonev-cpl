<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot CPL dan profil lulusan.
 *
 * Dibuat sebagai model sendiri (dipasang lewat ->using()) agar PK UUID tetap
 * terisi saat baris dibuat lewat relasi, mis. attach() atau sync().
 */
class PemetaanCplPl extends Pivot
{
    use HasUuids;

    protected $table = 'pemetaan_cpl_pl';

    protected $primaryKey = 'pemetaan_cpl_pl_id';

    public function cpl(): BelongsTo
    {
        return $this->belongsTo(Cpl::class, 'cpl_id', 'cpl_id');
    }

    public function profilLulusan(): BelongsTo
    {
        return $this->belongsTo(ProfilLulusan::class, 'profil_lulusan_id', 'profil_lulusan_id');
    }
}
