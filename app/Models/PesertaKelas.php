<?php

namespace App\Models;

use Database\Factories\PesertaKelasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot kelas kuliah dan mahasiswa pesertanya.
 *
 * Selain dipakai lewat ->using() agar PK UUID terisi saat attach()/sync(),
 * baris peserta juga punya data turunan: nilai asesmen dicatat per peserta
 * kelas, sehingga capaian dihitung per baris peserta_kelas (rumus 5.2).
 */
class PesertaKelas extends Pivot
{
    /** @use HasFactory<PesertaKelasFactory> */
    use HasFactory, HasUuids;

    protected $table = 'peserta_kelas';

    protected $primaryKey = 'peserta_kelas_id';

    public function kelasKuliah(): BelongsTo
    {
        return $this->belongsTo(KelasKuliah::class, 'kelas_kuliah_id', 'kelas_kuliah_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'mahasiswa_id');
    }

    public function nilaiAsesmen(): HasMany
    {
        return $this->hasMany(NilaiAsesmen::class, 'peserta_kelas_id', 'peserta_kelas_id');
    }
}
