<?php

namespace App\Models;

use App\Models\Concerns\BisaDicari;
use Database\Factories\CplFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cpl extends Model
{
    /** @use HasFactory<CplFactory> */
    use BisaDicari, HasFactory, HasUuids;

    protected $table = 'cpl';

    protected $primaryKey = 'cpl_id';

    protected $fillable = ['kurikulum_id', 'kode_cpl', 'kategori_cpl', 'deskripsi_cpl'];

    /** Kolom yang dicari lewat kotak "Cari data...". */
    protected array $kolomPencarian = ['kode_cpl', 'kategori_cpl', 'deskripsi_cpl'];

    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class, 'kurikulum_id', 'kurikulum_id');
    }

    public function cpmk(): HasMany
    {
        return $this->hasMany(Cpmk::class, 'cpl_id', 'cpl_id');
    }

    public function profilLulusan(): BelongsToMany
    {
        return $this->belongsToMany(ProfilLulusan::class, 'pemetaan_cpl_pl', 'cpl_id', 'profil_lulusan_id')
            ->using(PemetaanCplPl::class)
            ->withTimestamps();
    }

    public function bahanKajian(): BelongsToMany
    {
        return $this->belongsToMany(BahanKajian::class, 'pemetaan_cpl_bk', 'cpl_id', 'bahan_kajian_id')
            ->using(PemetaanCplBk::class)
            ->withTimestamps();
    }

    public function alasanTidakBisaDihapus(): ?string
    {
        return $this->cpmk()->exists()
            ? 'CPL tidak bisa dihapus karena masih memiliki CPMK.'
            : null;
    }
}
