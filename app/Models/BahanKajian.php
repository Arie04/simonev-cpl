<?php

namespace App\Models;

use Database\Factories\BahanKajianFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BahanKajian extends Model
{
    /** @use HasFactory<BahanKajianFactory> */
    use HasFactory, HasUuids;

    protected $table = 'bahan_kajian';

    protected $primaryKey = 'bahan_kajian_id';

    protected $fillable = [
        'kurikulum_id',
        'kode_bk',
        'nama_bahan_kajian',
        'kategori_bk',
        'bobot_min',
        'bobot_max',
        'cs_core',
        'ka_core',
        'referensi',
    ];

    protected function casts(): array
    {
        return [
            'bobot_min' => 'integer',
            'bobot_max' => 'integer',
            'cs_core' => 'integer',
            'ka_core' => 'integer',
        ];
    }

    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class, 'kurikulum_id', 'kurikulum_id');
    }

    public function cpl(): BelongsToMany
    {
        return $this->belongsToMany(Cpl::class, 'pemetaan_cpl_bk', 'bahan_kajian_id', 'cpl_id')
            ->using(PemetaanCplBk::class)
            ->withTimestamps();
    }

    public function mataKuliah(): BelongsToMany
    {
        return $this->belongsToMany(MataKuliah::class, 'pemetaan_bk_mk', 'bahan_kajian_id', 'mata_kuliah_id')
            ->using(PemetaanBkMk::class)
            ->withPivot('pemetaan_bk_mk_id', 'bobot_bk_mk')
            ->withTimestamps();
    }
}
