<?php

namespace App\Models;

use Database\Factories\EvaluasiKurikulumFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluasiKurikulum extends Model
{
    /** @use HasFactory<EvaluasiKurikulumFactory> */
    use HasFactory, HasUuids;

    protected $table = 'evaluasi_kurikulum';

    protected $primaryKey = 'evaluasi_kurikulum_id';

    protected $fillable = [
        'kurikulum_id',
        'tahap_evaluasi',
        'kinerja_mutu',
        'standar_kinerja_mutu',
        'kesenjangan',
        'tindak_lanjut',
    ];

    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class, 'kurikulum_id', 'kurikulum_id');
    }
}
