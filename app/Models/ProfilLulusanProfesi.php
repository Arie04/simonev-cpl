<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot profil lulusan dan profesi.
 *
 * Dibuat sebagai model sendiri (dipasang lewat ->using()) agar PK UUID tetap
 * terisi saat baris dibuat lewat relasi, mis. attach() atau sync().
 */
class ProfilLulusanProfesi extends Pivot
{
    use HasUuids;

    protected $table = 'profil_lulusan_profesi';

    protected $primaryKey = 'profil_lulusan_profesi_id';

    public function profilLulusan(): BelongsTo
    {
        return $this->belongsTo(ProfilLulusan::class, 'profil_lulusan_id', 'profil_lulusan_id');
    }

    public function profesi(): BelongsTo
    {
        return $this->belongsTo(Profesi::class, 'profesi_id', 'profesi_id');
    }
}
