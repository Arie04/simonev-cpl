<?php

namespace App\Models;

use Database\Factories\SubCpmkFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubCpmk extends Model
{
    /** @use HasFactory<SubCpmkFactory> */
    use HasFactory, HasUuids;

    protected $table = 'sub_cpmk';

    protected $primaryKey = 'sub_cpmk_id';

    protected $fillable = [
        'pemetaan_cpmk_mk_id',
        'kode_sub_cpmk',
        'deskripsi_sub_cpmk',
        'bobot_sub_cpmk',
        'materi',
        'pertemuan_mulai',
        'pertemuan_selesai',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'bobot_sub_cpmk' => 'decimal:2',
            'pertemuan_mulai' => 'integer',
            'pertemuan_selesai' => 'integer',
        ];
    }

    // Sub-CPMK menempel ke pasangan CPMK–MK, bukan ke CPMK langsung.
    public function pemetaanCpmkMk(): BelongsTo
    {
        return $this->belongsTo(PemetaanCpmkMk::class, 'pemetaan_cpmk_mk_id', 'pemetaan_cpmk_mk_id');
    }

    public function metodePenilaian(): BelongsToMany
    {
        return $this->belongsToMany(MetodePenilaian::class, 'sub_cpmk_penilaian', 'sub_cpmk_id', 'metode_penilaian_id')
            ->using(SubCpmkPenilaian::class)
            ->withPivot('sub_cpmk_penilaian_id', 'teknik_penilaian', 'instrumen', 'kriteria')
            ->withTimestamps();
    }

    public function rencanaAsesmen(): BelongsToMany
    {
        return $this->belongsToMany(RencanaAsesmen::class, 'rencana_asesmen_sub_cpmk', 'sub_cpmk_id', 'rencana_asesmen_id')
            ->using(RencanaAsesmenSubCpmk::class)
            ->withPivot('rencana_asesmen_sub_cpmk_id', 'bobot_kontribusi')
            ->withTimestamps();
    }
}
