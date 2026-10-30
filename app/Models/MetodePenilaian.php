<?php

namespace App\Models;

use App\Models\Concerns\BisaDicari;
use Database\Factories\MetodePenilaianFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodePenilaian extends Model
{
    /** @use HasFactory<MetodePenilaianFactory> */
    use BisaDicari, HasFactory, HasUuids;

    protected $table = 'metode_penilaian';

    protected $primaryKey = 'metode_penilaian_id';

    protected $fillable = ['kode_metode', 'nama_metode'];

    /** Kolom yang dicari lewat kotak "Cari data...". */
    protected array $kolomPencarian = ['kode_metode', 'nama_metode'];

    public function subCpmk(): BelongsToMany
    {
        return $this->belongsToMany(SubCpmk::class, 'sub_cpmk_penilaian', 'metode_penilaian_id', 'sub_cpmk_id')
            ->using(SubCpmkPenilaian::class)
            ->withPivot('sub_cpmk_penilaian_id', 'teknik_penilaian', 'instrumen', 'kriteria')
            ->withTimestamps();
    }

    public function rencanaAsesmen(): HasMany
    {
        return $this->hasMany(RencanaAsesmen::class, 'metode_penilaian_id', 'metode_penilaian_id');
    }

    public function alasanTidakBisaDihapus(): ?string
    {
        if ($this->rencanaAsesmen()->exists()) {
            return 'Metode penilaian tidak bisa dihapus karena masih dipakai rencana asesmen.';
        }

        if ($this->subCpmk()->exists()) {
            return 'Metode penilaian tidak bisa dihapus karena masih dipakai rincian penilaian Sub-CPMK.';
        }

        return null;
    }
}
