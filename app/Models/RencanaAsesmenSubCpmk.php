<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot rencana asesmen dan Sub-CPMK, beserta bobot_kontribusi.
 *
 * bobot_kontribusi adalah dasar perhitungan capaian (5.1): bagian asesmen
 * yang mengukur Sub-CPMK ini, dalam % nilai akhir MK. Dibuat sebagai model
 * sendiri agar PK UUID tetap terisi saat attach()/sync().
 */
class RencanaAsesmenSubCpmk extends Pivot
{
    use HasUuids;

    protected $table = 'rencana_asesmen_sub_cpmk';

    protected $primaryKey = 'rencana_asesmen_sub_cpmk_id';

    protected function casts(): array
    {
        return [
            'bobot_kontribusi' => 'decimal:2',
        ];
    }

    public function rencanaAsesmen(): BelongsTo
    {
        return $this->belongsTo(RencanaAsesmen::class, 'rencana_asesmen_id', 'rencana_asesmen_id');
    }

    public function subCpmk(): BelongsTo
    {
        return $this->belongsTo(SubCpmk::class, 'sub_cpmk_id', 'sub_cpmk_id');
    }
}
