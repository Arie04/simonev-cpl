<?php

namespace App\Models;

use Database\Factories\PemetaanCpmkMkFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot CPMK dan mata kuliah, beserta bobot_cpmk (target "skor maks CPMK").
 *
 * Selain dipakai lewat ->using() agar PK UUID terisi saat attach()/sync(),
 * pemetaan ini juga punya data turunan: Sub-CPMK menempel ke pasangan
 * CPMK–MK, bukan ke CPMK langsung (bagian 4 spesifikasi).
 */
class PemetaanCpmkMk extends Pivot
{
    /** @use HasFactory<PemetaanCpmkMkFactory> */
    use HasFactory, HasUuids;

    protected $table = 'pemetaan_cpmk_mk';

    protected $primaryKey = 'pemetaan_cpmk_mk_id';

    protected function casts(): array
    {
        return [
            'bobot_cpmk' => 'decimal:2',
        ];
    }

    public function cpmk(): BelongsTo
    {
        return $this->belongsTo(Cpmk::class, 'cpmk_id', 'cpmk_id');
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id', 'mata_kuliah_id');
    }

    public function subCpmk(): HasMany
    {
        return $this->hasMany(SubCpmk::class, 'pemetaan_cpmk_mk_id', 'pemetaan_cpmk_mk_id');
    }

    /**
     * Label pasangan untuk tampilan, mis. "CPMK021 · INF101" (KD-25).
     */
    public function label(): string
    {
        return $this->cpmk->kode_cpmk.' · '.$this->mataKuliah->kode_mata_kuliah;
    }

    /**
     * Jumlah bobot Sub-CPMK milik pasangan ini. Seharusnya sama dengan bobot_cpmk (bagian 6).
     */
    public function totalBobotSubCpmk(): float
    {
        return (float) $this->subCpmk->sum('bobot_sub_cpmk');
    }
}
