<?php

namespace App\Models;

use Database\Factories\KelasKuliahFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * Pencarian di halaman Kelas Kuliah: kode atau nama mata kuliah, atau nama kelas.
     */
    public function scopeCari(Builder $query, ?string $kataKunci): void
    {
        if (blank($kataKunci)) {
            return;
        }

        $pola = '%'.addcslashes(trim($kataKunci), '\\%_').'%';

        $query->where(function (Builder $query) use ($pola) {
            $query->where('nama_kelas', 'like', $pola)
                ->orWhereHas('mataKuliah', fn (Builder $query) => $query
                    ->where('kode_mata_kuliah', 'like', $pola)
                    ->orWhere('nama_mata_kuliah', 'like', $pola));
        });
    }

    /**
     * Nama kelas untuk tampilan, mis. "INF101 Matematika Diskrit – A".
     */
    public function nama(): string
    {
        return "{$this->mataKuliah->kode_mata_kuliah} {$this->mataKuliah->nama_mata_kuliah} – {$this->nama_kelas}";
    }

    /**
     * Kelas lain untuk MK yang sama di periode yang sama, tempat mahasiswa ini sudah terdaftar.
     * Seorang mahasiswa hanya boleh di satu kelas per MK per periode (KD-37).
     */
    public function kelasLainYangDiikuti(Mahasiswa $mahasiswa): ?self
    {
        return self::where('mata_kuliah_id', $this->mata_kuliah_id)
            ->where('periode_akademik_id', $this->periode_akademik_id)
            ->whereKeyNot($this->getKey())
            ->whereHas('pesertaKelas', fn (Builder $query) => $query->where('mahasiswa_id', $mahasiswa->mahasiswa_id))
            ->first();
    }

    /**
     * Mata kuliah dan periode tidak bisa diganti setelah kelas berisi peserta atau rencana
     * asesmen, karena Sub-CPMK asesmen dan aturan KD-37 bergantung pada keduanya (KD-57).
     */
    public function bisaGantiMataKuliahDanPeriode(): bool
    {
        return $this->pesertaKelas()->doesntExist() && $this->rencanaAsesmen()->doesntExist();
    }

    public function alasanTidakBisaDihapus(): ?string
    {
        return $this->rencanaAsesmen()->exists()
            ? 'Kelas tidak bisa dihapus karena sudah punya rencana asesmen.'
            : null;
    }

    /**
     * Sub-CPMK yang boleh dipetakan ke asesmen kelas ini: hanya Sub-CPMK milik MK kelas ini
     * (bagian 6 spesifikasi).
     *
     * @return Collection<int, SubCpmk>
     */
    public function daftarSubCpmk(): Collection
    {
        return SubCpmk::with('pemetaanCpmkMk.cpmk')
            ->whereHas('pemetaanCpmkMk', fn (Builder $query) => $query->where('mata_kuliah_id', $this->mata_kuliah_id))
            ->get()
            ->sortBy(fn (SubCpmk $subCpmk) => $subCpmk->pemetaanCpmkMk->cpmk->kode_cpmk.' '.$subCpmk->kode_sub_cpmk)
            ->values();
    }
}
