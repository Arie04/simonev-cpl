<?php

namespace App\Models;

use App\Models\Concerns\BisaDicari;
use Database\Factories\ProfilLulusanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProfilLulusan extends Model
{
    /** @use HasFactory<ProfilLulusanFactory> */
    use BisaDicari, HasFactory, HasUuids;

    protected $table = 'profil_lulusan';

    protected $primaryKey = 'profil_lulusan_id';

    protected $fillable = ['kurikulum_id', 'kode_pl', 'deskripsi_pl', 'unsur', 'sumber'];

    /** Kolom yang dicari lewat kotak "Cari data...". */
    protected array $kolomPencarian = ['kode_pl', 'deskripsi_pl', 'unsur', 'sumber'];

    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class, 'kurikulum_id', 'kurikulum_id');
    }

    public function profesi(): BelongsToMany
    {
        return $this->belongsToMany(Profesi::class, 'profil_lulusan_profesi', 'profil_lulusan_id', 'profesi_id')
            ->using(ProfilLulusanProfesi::class)
            ->withTimestamps();
    }

    public function cpl(): BelongsToMany
    {
        return $this->belongsToMany(Cpl::class, 'pemetaan_cpl_pl', 'profil_lulusan_id', 'cpl_id')
            ->using(PemetaanCplPl::class)
            ->withTimestamps();
    }
}
