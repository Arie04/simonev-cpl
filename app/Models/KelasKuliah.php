<?php

namespace App\Models;

use Database\Factories\KelasKuliahFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelasKuliah extends Model
{
    /** @use HasFactory<KelasKuliahFactory> */
    use HasFactory, HasUuids;

    protected $table = 'kelas_kuliah';

    protected $primaryKey = 'kelas_kuliah_id';

    protected $fillable = ['mata_kuliah_id', 'periode_akademik_id', 'nama_kelas'];

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id', 'mata_kuliah_id');
    }

    public function periodeAkademik(): BelongsTo
    {
        return $this->belongsTo(PeriodeAkademik::class, 'periode_akademik_id', 'periode_akademik_id');
    }

    public function dosen(): BelongsToMany
    {
        return $this->belongsToMany(Dosen::class, 'dosen_kelas', 'kelas_kuliah_id', 'dosen_id')
            ->using(DosenKelas::class)
            ->withTimestamps();
    }

    public function mahasiswa(): BelongsToMany
    {
        return $this->belongsToMany(Mahasiswa::class, 'peserta_kelas', 'kelas_kuliah_id', 'mahasiswa_id')
            ->using(PesertaKelas::class)
            ->withPivot('peserta_kelas_id')
            ->withTimestamps();
    }

    public function pesertaKelas(): HasMany
    {
        return $this->hasMany(PesertaKelas::class, 'kelas_kuliah_id', 'kelas_kuliah_id');
    }

    public function rencanaAsesmen(): HasMany
    {
        return $this->hasMany(RencanaAsesmen::class, 'kelas_kuliah_id', 'kelas_kuliah_id');
    }
}
