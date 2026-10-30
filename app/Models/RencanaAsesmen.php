<?php

namespace App\Models;

use Database\Factories\RencanaAsesmenFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RencanaAsesmen extends Model
{
    /** @use HasFactory<RencanaAsesmenFactory> */
    use HasFactory, HasUuids;

    protected $table = 'rencana_asesmen';

    protected $primaryKey = 'rencana_asesmen_id';

    protected $fillable = [
        'kelas_kuliah_id',
        'metode_penilaian_id',
        'nama_asesmen',
        'bobot_asesmen',
        'nilai_maksimum',
        'tanggal_asesmen',
    ];

    protected function casts(): array
    {
        return [
            'bobot_asesmen' => 'decimal:2',
            'nilai_maksimum' => 'decimal:2',
            'tanggal_asesmen' => 'date',
        ];
    }

    public function kelasKuliah(): BelongsTo
    {
        return $this->belongsTo(KelasKuliah::class, 'kelas_kuliah_id', 'kelas_kuliah_id');
    }

    public function metodePenilaian(): BelongsTo
    {
        return $this->belongsTo(MetodePenilaian::class, 'metode_penilaian_id', 'metode_penilaian_id');
    }

    // Pemetaan asesmen ke Sub-CPMK beserta bobot_kontribusi, dasar perhitungan capaian (5.1).
    public function subCpmk(): BelongsToMany
    {
        return $this->belongsToMany(SubCpmk::class, 'rencana_asesmen_sub_cpmk', 'rencana_asesmen_id', 'sub_cpmk_id')
            ->using(RencanaAsesmenSubCpmk::class)
            ->withPivot('rencana_asesmen_sub_cpmk_id', 'bobot_kontribusi')
            ->withTimestamps();
    }

    public function nilaiAsesmen(): HasMany
    {
        return $this->hasMany(NilaiAsesmen::class, 'rencana_asesmen_id', 'rencana_asesmen_id');
    }
}
