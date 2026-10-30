<?php

namespace App\Models;

use App\Models\Concerns\BisaDicari;
use Database\Factories\DosenFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Dosen extends Model
{
    /** @use HasFactory<DosenFactory> */
    use BisaDicari, HasFactory, HasUuids;

    protected $table = 'dosen';

    protected $primaryKey = 'dosen_id';

    protected $fillable = ['user_id', 'nidn', 'nama'];

    /** Kolom yang dicari lewat kotak "Cari data...". */
    protected array $kolomPencarian = ['nidn', 'nama'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function kelasKuliah(): BelongsToMany
    {
        return $this->belongsToMany(KelasKuliah::class, 'dosen_kelas', 'dosen_id', 'kelas_kuliah_id')
            ->using(DosenKelas::class)
            ->withTimestamps();
    }

    /**
     * Membuat akun login, memberi peran DOSEN, lalu membuat data dosen (bagian 7.6.4 spesifikasi).
     */
    public static function buatBesertaAkun(array $data): self
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'nama' => $data['nama'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
            ]);
            $user->roles()->attach(Role::ambil(Role::DOSEN));

            return $user->dosen()->create([
                'nidn' => $data['nidn'],
                'nama' => $data['nama'],
            ]);
        });
    }

    /**
     * Nama disimpan sama di dosen.nama dan users.nama.
     */
    public function ubahBesertaAkun(array $data): void
    {
        DB::transaction(function () use ($data) {
            $this->update(['nidn' => $data['nidn'], 'nama' => $data['nama']]);
            $this->user->update(['nama' => $data['nama'], 'email' => $data['email'] ?? null]);
        });
    }

    /**
     * Menghapus data dosen dan peran DOSEN. Akunnya ikut dihapus kecuali masih punya
     * peran lain, mis. KOPRODI (KD-36).
     */
    public function hapusBesertaAkun(): void
    {
        DB::transaction(function () {
            $user = $this->user;
            $this->delete();
            $user->cabutPeranLaluHapusJikaKosong(Role::DOSEN);
        });
    }

    public function alasanTidakBisaDihapus(): ?string
    {
        return $this->kelasKuliah()->exists()
            ? 'Dosen tidak bisa dihapus karena masih mengampu kelas.'
            : null;
    }
}
