<?php

namespace App\Models;

use Database\Factories\PeriodeAkademikFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodeAkademik extends Model
{
    /** @use HasFactory<PeriodeAkademikFactory> */
    use HasFactory, HasUuids;

    public const GANJIL = 'Ganjil';

    public const GENAP = 'Genap';

    protected $table = 'periode_akademik';

    protected $primaryKey = 'periode_akademik_id';

    protected $fillable = ['tahun_akademik', 'semester_periode'];

    public function kelasKuliah(): HasMany
    {
        return $this->hasMany(KelasKuliah::class, 'periode_akademik_id', 'periode_akademik_id');
    }
}
