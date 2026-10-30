<?php

namespace App\Models;

use App\Models\Concerns\BisaDicari;
use Database\Factories\KurikulumFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kurikulum extends Model
{
    /** @use HasFactory<KurikulumFactory> */
    use BisaDicari, HasFactory, HasUuids;

    protected $table = 'kurikulum';

    protected $primaryKey = 'kurikulum_id';

    protected $fillable = ['program_studi_id', 'nama_kurikulum', 'tahun_berlaku'];

    /** Kolom yang dicari lewat kotak "Cari data...". */
    protected array $kolomPencarian = ['nama_kurikulum', 'tahun_berlaku'];

    protected function casts(): array
    {
        return [
            'tahun_berlaku' => 'integer',
        ];
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_id', 'program_studi_id');
    }

    public function evaluasiKurikulum(): HasMany
    {
        return $this->hasMany(EvaluasiKurikulum::class, 'kurikulum_id', 'kurikulum_id');
    }

    public function profilLulusan(): HasMany
    {
        return $this->hasMany(ProfilLulusan::class, 'kurikulum_id', 'kurikulum_id');
    }

    public function profesi(): HasMany
    {
        return $this->hasMany(Profesi::class, 'kurikulum_id', 'kurikulum_id');
    }

    public function cpl(): HasMany
    {
        return $this->hasMany(Cpl::class, 'kurikulum_id', 'kurikulum_id');
    }

    public function bahanKajian(): HasMany
    {
        return $this->hasMany(BahanKajian::class, 'kurikulum_id', 'kurikulum_id');
    }

    public function mataKuliah(): HasMany
    {
        return $this->hasMany(MataKuliah::class, 'kurikulum_id', 'kurikulum_id');
    }

    /**
     * Kurikulum terbaru = tahun_berlaku terbesar, bukan urutan id (bagian 4 spesifikasi).
     */
    public static function terbaru(): ?self
    {
        return self::orderByDesc('tahun_berlaku')->first();
    }

    /**
     * Daftar kurikulum untuk dropdown, dari yang terbaru.
     */
    public static function pilihan(): Collection
    {
        return self::orderByDesc('tahun_berlaku')->get();
    }

    public function alasanTidakBisaDihapus(): ?string
    {
        $dataTerkait = array_filter([
            'evaluasi kurikulum' => $this->evaluasiKurikulum()->exists(),
            'profil lulusan' => $this->profilLulusan()->exists(),
            'profesi' => $this->profesi()->exists(),
            'CPL' => $this->cpl()->exists(),
            'bahan kajian' => $this->bahanKajian()->exists(),
            'mata kuliah' => $this->mataKuliah()->exists(),
        ]);

        return $dataTerkait === []
            ? null
            : 'Kurikulum tidak bisa dihapus karena masih memiliki data '.implode(', ', array_keys($dataTerkait)).'.';
    }
}
