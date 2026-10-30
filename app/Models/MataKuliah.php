<?php

namespace App\Models;

use App\Models\Concerns\BisaDicari;
use Database\Factories\MataKuliahFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliah extends Model
{
    /** @use HasFactory<MataKuliahFactory> */
    use BisaDicari, HasFactory, HasUuids;

    protected $table = 'mata_kuliah';

    protected $primaryKey = 'mata_kuliah_id';

    protected $fillable = [
        'kurikulum_id',
        'kode_mata_kuliah',
        'nama_mata_kuliah',
        'semester',
        'jumlah_sks',
        'jenis_mata_kuliah',
        'level_bloom_mk',
        'keterangan',
    ];

    /** Kolom yang dicari lewat kotak "Cari data...". */
    protected array $kolomPencarian = ['kode_mata_kuliah', 'nama_mata_kuliah', 'jenis_mata_kuliah'];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'jumlah_sks' => 'integer',
        ];
    }

    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class, 'kurikulum_id', 'kurikulum_id');
    }

    public function bahanKajian(): BelongsToMany
    {
        return $this->belongsToMany(BahanKajian::class, 'pemetaan_bk_mk', 'mata_kuliah_id', 'bahan_kajian_id')
            ->using(PemetaanBkMk::class)
            ->withPivot('pemetaan_bk_mk_id', 'bobot_bk_mk')
            ->withTimestamps();
    }

    public function cpmk(): BelongsToMany
    {
        return $this->belongsToMany(Cpmk::class, 'pemetaan_cpmk_mk', 'mata_kuliah_id', 'cpmk_id')
            ->using(PemetaanCpmkMk::class)
            ->withPivot('pemetaan_cpmk_mk_id', 'bobot_cpmk')
            ->withTimestamps();
    }

    public function pemetaanCpmkMk(): HasMany
    {
        return $this->hasMany(PemetaanCpmkMk::class, 'mata_kuliah_id', 'mata_kuliah_id');
    }

    public function kelasKuliah(): HasMany
    {
        return $this->hasMany(KelasKuliah::class, 'mata_kuliah_id', 'mata_kuliah_id');
    }

    public function alasanTidakBisaDihapus(): ?string
    {
        if ($this->kelasKuliah()->exists()) {
            return 'Mata kuliah tidak bisa dihapus karena masih dipakai kelas kuliah.';
        }

        // Pemetaan CPMK–MK ikut terhapus, kecuali yang sudah punya Sub-CPMK (KD-20).
        if ($this->pemetaanCpmkMk()->has('subCpmk')->exists()) {
            return 'Mata kuliah tidak bisa dihapus karena pemetaan CPMK-nya masih memiliki Sub-CPMK.';
        }

        return null;
    }
}
