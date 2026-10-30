<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids;

    protected $table = 'users';

    protected $primaryKey = 'user_id';

    protected $fillable = ['nama', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->using(UserRole::class)
            ->withTimestamps();
    }

    public function dosen(): HasOne
    {
        return $this->hasOne(Dosen::class, 'user_id', 'user_id');
    }

    public function mahasiswa(): HasOne
    {
        return $this->hasOne(Mahasiswa::class, 'user_id', 'user_id');
    }

    /**
     * Mencari akun dari isian login: email, lalu NPM (mahasiswa.nim), lalu NIDN (dosen.nidn).
     */
    public static function cariBerdasarkanIdentitas(string $identitas): ?self
    {
        return self::where('email', $identitas)->first()
            ?? Mahasiswa::where('nim', $identitas)->first()?->user
            ?? Dosen::where('nidn', $identitas)->first()?->user;
    }

    /**
     * Mengecek apakah akun ini memiliki peran tertentu, mis. Role::DOSEN.
     */
    public function punyaPeran(string $kodeRole): bool
    {
        return $this->roles->contains('kode_role', $kodeRole);
    }

    /**
     * Inisial nama (maksimal dua huruf) untuk avatar di top bar, mis. "DD" untuk "Dosen Demo".
     */
    public function inisial(): string
    {
        return Str::of($this->nama)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $kata) => Str::upper(Str::substr($kata, 0, 1)))
            ->implode('');
    }

    /**
     * Pencarian di halaman Akun Pengguna: nama, email, NIDN, atau NPM.
     */
    public function scopeCari(Builder $query, ?string $kataKunci): void
    {
        if (blank($kataKunci)) {
            return;
        }

        $pola = '%'.addcslashes(trim($kataKunci), '\\%_').'%';

        $query->where(function (Builder $query) use ($pola) {
            $query->where('nama', 'like', $pola)
                ->orWhere('email', 'like', $pola)
                ->orWhereHas('dosen', fn (Builder $query) => $query->where('nidn', 'like', $pola))
                ->orWhereHas('mahasiswa', fn (Builder $query) => $query->where('nim', 'like', $pola));
        });
    }

    /**
     * Peran yang boleh dicentang untuk akun ini (KD-36). ADMIN dan KOPRODI selalu boleh;
     * DOSEN dan MAHASISWA hanya untuk akun yang punya data dosen/mahasiswa.
     *
     * @return list<string>
     */
    public function peranYangBoleh(): array
    {
        $daftarPeran = [Role::ADMIN, Role::KOPRODI];

        if ($this->dosen !== null) {
            $daftarPeran[] = Role::DOSEN;
        }

        if ($this->mahasiswa !== null) {
            $daftarPeran[] = Role::MAHASISWA;
        }

        return $daftarPeran;
    }

    /**
     * Mengganti seluruh peran akun dengan daftar kode peran yang dipilih.
     *
     * @param  list<string>  $daftarKodeRole
     */
    public function simpanPeran(array $daftarKodeRole): void
    {
        $this->roles()->sync(
            collect($daftarKodeRole)->map(fn (string $kodeRole) => Role::ambil($kodeRole)->role_id)
        );
    }

    /**
     * Mencabut satu peran. Jika akun tidak punya peran lagi, akunnya dihapus (KD-36).
     */
    public function cabutPeranLaluHapusJikaKosong(string $kodeRole): void
    {
        $this->roles()->detach(Role::ambil($kodeRole));

        if ($this->roles()->doesntExist()) {
            $this->delete();
        }
    }

    public function alasanTidakBisaDihapus(): ?string
    {
        return $this->dosen()->exists() || $this->mahasiswa()->exists()
            ? 'Akun ini terhubung ke data dosen atau mahasiswa. Hapus lewat menu Data Master Dosen atau Mahasiswa.'
            : null;
    }
}
