<?php

namespace App\Models;

use App\Models\Concerns\BisaDicari;
use Database\Factories\PeriodeAkademikFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodeAkademik extends Model
{
    /** @use HasFactory<PeriodeAkademikFactory> */
    use BisaDicari, HasFactory, HasUuids;

    public const GANJIL = 'Ganjil';

    public const GENAP = 'Genap';

    protected $table = 'periode_akademik';

    protected $primaryKey = 'periode_akademik_id';

    protected $fillable = ['tahun_akademik', 'semester_periode'];

    /** Kolom yang dicari lewat kotak "Cari data...". */
    protected array $kolomPencarian = ['tahun_akademik', 'semester_periode'];

    public function kelasKuliah(): HasMany
    {
        return $this->hasMany(KelasKuliah::class, 'periode_akademik_id', 'periode_akademik_id');
    }

    /**
     * Urutan dari periode terbaru: tahun_akademik terbesar, lalu Genap sebelum Ganjil karena
     * semester Genap berjalan setelah Ganjil dalam tahun akademik yang sama (bagian 4 spesifikasi).
     */
    public function scopeUrutTerbaru(Builder $query): void
    {
        $query->orderByDesc('tahun_akademik')
            ->orderByRaw("FIELD(semester_periode, 'Genap', 'Ganjil')");
    }

    /**
     * Periode aktif = periode terbaru yang sudah memiliki kelas (bagian 13 spesifikasi, KD-45).
     */
    public static function aktif(): ?self
    {
        return self::has('kelasKuliah')->urutTerbaru()->first();
    }

    /**
     * Nama periode untuk tampilan, mis. "2026/2027 Ganjil".
     */
    public function nama(): string
    {
        return "{$this->tahun_akademik} {$this->semester_periode}";
    }

    public function alasanTidakBisaDihapus(): ?string
    {
        return $this->kelasKuliah()->exists()
            ? 'Periode akademik tidak bisa dihapus karena sudah punya kelas.'
            : null;
    }
}
