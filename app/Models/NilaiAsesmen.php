<?php

namespace App\Models;

use Database\Factories\NilaiAsesmenFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiAsesmen extends Model
{
    /** @use HasFactory<NilaiAsesmenFactory> */
    use HasFactory, HasUuids;

    protected $table = 'nilai_asesmen';

    protected $primaryKey = 'nilai_asesmen_id';

    protected $fillable = ['rencana_asesmen_id', 'peserta_kelas_id', 'nilai', 'catatan'];

    protected function casts(): array
    {
        return [
            'nilai' => 'decimal:2',
        ];
    }

    public function rencanaAsesmen(): BelongsTo
    {
        return $this->belongsTo(RencanaAsesmen::class, 'rencana_asesmen_id', 'rencana_asesmen_id');
    }

    public function pesertaKelas(): BelongsTo
    {
        return $this->belongsTo(PesertaKelas::class, 'peserta_kelas_id', 'peserta_kelas_id');
    }
}
