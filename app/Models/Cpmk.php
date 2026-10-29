<?php

namespace App\Models;

use Database\Factories\CpmkFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cpmk extends Model
{
    /** @use HasFactory<CpmkFactory> */
    use HasFactory, HasUuids;

    protected $table = 'cpmk';

    protected $primaryKey = 'cpmk_id';

    protected $fillable = ['cpl_id', 'kode_cpmk', 'deskripsi_cpmk'];

    public function cpl(): BelongsTo
    {
        return $this->belongsTo(Cpl::class, 'cpl_id', 'cpl_id');
    }

    public function mataKuliah(): BelongsToMany
    {
        return $this->belongsToMany(MataKuliah::class, 'pemetaan_cpmk_mk', 'cpmk_id', 'mata_kuliah_id')
            ->using(PemetaanCpmkMk::class)
            ->withPivot('pemetaan_cpmk_mk_id', 'bobot_cpmk')
            ->withTimestamps();
    }

    public function pemetaanCpmkMk(): HasMany
    {
        return $this->hasMany(PemetaanCpmkMk::class, 'cpmk_id', 'cpmk_id');
    }
}
