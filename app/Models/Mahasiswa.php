<?php

namespace App\Models;

use App\Models\Concerns\BisaDicari;
use Database\Factories\MahasiswaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Mahasiswa extends Model
{
    /** @use HasFactory<MahasiswaFactory> */
    use BisaDicari, HasFactory, HasUuids;

    protected $table = 'mahasiswa';

    protected $primaryKey = 'mahasiswa_id';

    protected $fillable = ['user_id', 'nim', 'nama', 'angkatan'];

    /** Kolom yang dicari lewat kotak "Cari data...". */
    protected array $kolomPencarian = ['nim', 'nama', 'angkatan'];

    protected function casts(): array
    {
        return [
            'angkatan' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function pesertaKelas(): HasMany
    {
        return $this->hasMany(PesertaKelas::class, 'mahasiswa_id', 'mahasiswa_id');
    }

    public function kelasKuliah(): BelongsToMany
    {
        return $this->belongsToMany(KelasKuliah::class, 'peserta_kelas', 'mahasiswa_id', 'kelas_kuliah_id')
            ->using(PesertaKelas::class)
            ->withPivot('peserta_kelas_id')
            ->withTimestamps();
    }

    /**
     * Membuat akun login, memberi peran MAHASISWA, lalu membuat data mahasiswa (bagian 7.6.4 spesifikasi).
     */
    public static function buatBesertaAkun(array $data): self
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'nama' => $data['nama'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
            ]);
            $user->roles()->attach(Role::ambil(Role::MAHASISWA));

            return $user->mahasiswa()->create([
                'nim' => $data['nim'],
                'nama' => $data['nama'],
                'angkatan' => $data['angkatan'],
            ]);
        });
    }

    /**
     * Nama disimpan sama di mahasiswa.nama dan users.nama.
     */
    public function ubahBesertaAkun(array $data): void
    {
        DB::transaction(function () use ($data) {
            $this->update([
                'nim' => $data['nim'],
                'nama' => $data['nama'],
                'angkatan' => $data['angkatan'],
            ]);
            $this->user->update(['nama' => $data['nama'], 'email' => $data['email'] ?? null]);
        });
    }

    /**
     * Menghapus data mahasiswa dan peran MAHASISWA. Akunnya ikut dihapus kecuali masih
     * punya peran lain (KD-36).
     */
    public function hapusBesertaAkun(): void
    {
        DB::transaction(function () {
            $user = $this->user;
            $this->delete();
            $user->cabutPeranLaluHapusJikaKosong(Role::MAHASISWA);
        });
    }

    public function alasanTidakBisaDihapus(): ?string
    {
        return $this->pesertaKelas()->exists()
            ? 'Mahasiswa tidak bisa dihapus karena masih terdaftar di kelas.'
            : null;
    }
}
