<?php

namespace App\Services;

use App\Models\BahanKajian;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use App\Models\PemetaanCpmkMk;
use App\Models\ProfilLulusan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Data matriks Pemetaan Kurikulum (bagian 7.6.5 spesifikasi) untuk satu jenis pemetaan dalam
 * satu kurikulum. Dipakai halaman Admin (bisa diubah) dan Data Kurikulum Koprodi (read-only).
 *
 * Baris matriks adalah sisi kiri nama pemetaan dan kolomnya sisi kanan (KD-44). Setiap sel
 * diberi kunci "<id baris>|<id kolom>".
 */
class MatriksPemetaan
{
    public const JENIS = [
        'cpl-pl' => 'CPL – Profil Lulusan',
        'cpl-bk' => 'CPL – Bahan Kajian',
        'bk-mk' => 'Bahan Kajian – Mata Kuliah',
        'cpmk-mk' => 'CPMK – Mata Kuliah',
    ];

    /** @var Collection<int, Model> */
    public readonly Collection $daftarBaris;

    /** @var Collection<int, Model> */
    public readonly Collection $daftarKolom;

    public function __construct(public readonly string $jenis, public readonly Kurikulum $kurikulum)
    {
        $relasi = $this->relasi();

        $this->daftarBaris = match ($jenis) {
            'cpl-pl', 'cpl-bk' => $kurikulum->cpl()->with($relasi)->orderBy('kode_cpl')->get(),
            'bk-mk' => $kurikulum->bahanKajian()->with($relasi)->orderBy('kode_bk')->get(),
            'cpmk-mk' => Cpmk::with(['cpl', $relasi])
                ->whereHas('cpl', fn (Builder $query) => $query->where('kurikulum_id', $kurikulum->kurikulum_id))
                ->orderBy('kode_cpmk')
                ->get(),
        };

        $this->daftarKolom = match ($jenis) {
            'cpl-pl' => $kurikulum->profilLulusan()->orderBy('kode_pl')->get(),
            'cpl-bk' => $kurikulum->bahanKajian()->orderBy('kode_bk')->get(),
            'bk-mk', 'cpmk-mk' => $kurikulum->mataKuliah()->orderBy('semester')->orderBy('kode_mata_kuliah')->get(),
        };
    }

    /**
     * Jenis pemetaan dari dropdown; jika kosong atau tidak dikenal, dipakai CPL–Profil Lulusan.
     */
    public static function jenisAtauBawaan(mixed $jenis): string
    {
        return is_string($jenis) && array_key_exists($jenis, self::JENIS) ? $jenis : 'cpl-pl';
    }

    /**
     * Nama relasi belongsToMany dari model baris ke model kolom.
     */
    public function relasi(): string
    {
        return match ($this->jenis) {
            'cpl-pl' => 'profilLulusan',
            'cpl-bk' => 'bahanKajian',
            'bk-mk', 'cpmk-mk' => 'mataKuliah',
        };
    }

    /**
     * Kolom bobot di tabel pivot, atau null untuk pemetaan tanpa bobot.
     */
    public function kolomBobot(): ?string
    {
        return match ($this->jenis) {
            'bk-mk' => 'bobot_bk_mk',
            'cpmk-mk' => 'bobot_cpmk',
            default => null,
        };
    }

    public function judulBaris(): string
    {
        return match ($this->jenis) {
            'cpl-pl', 'cpl-bk' => 'CPL',
            'bk-mk' => 'Bahan Kajian',
            'cpmk-mk' => 'CPMK',
        };
    }

    public function judulKolom(): string
    {
        return match ($this->jenis) {
            'cpl-pl' => 'Profil Lulusan',
            'cpl-bk' => 'Bahan Kajian',
            'bk-mk', 'cpmk-mk' => 'Mata Kuliah',
        };
    }

    /**
     * Kode yang tampil di judul baris atau kolom, mis. "CPL01" atau "INF101".
     */
    public function kode(Model $data): string
    {
        return match (true) {
            $data instanceof Cpl => $data->kode_cpl,
            $data instanceof ProfilLulusan => $data->kode_pl,
            $data instanceof BahanKajian => $data->kode_bk,
            $data instanceof MataKuliah => $data->kode_mata_kuliah,
            $data instanceof Cpmk => $data->kode_cpmk,
        };
    }

    /**
     * Keterangan singkat di bawah kode, mis. deskripsi CPL atau nama mata kuliah.
     */
    public function keterangan(Model $data): string
    {
        return match (true) {
            $data instanceof Cpl => $data->deskripsi_cpl,
            $data instanceof ProfilLulusan => $data->deskripsi_pl,
            $data instanceof BahanKajian => $data->nama_bahan_kajian,
            $data instanceof MataKuliah => $data->nama_mata_kuliah,
            $data instanceof Cpmk => $data->cpl->kode_cpl.' · '.$data->deskripsi_cpmk,
        };
    }

    public static function kunci(string $idBaris, string $idKolom): string
    {
        return $idBaris.'|'.$idKolom;
    }

    /**
     * Pasangan yang sudah terhubung beserta bobotnya.
     *
     * @return array<string, float|null> kunci sel => bobot (null untuk pemetaan tanpa bobot)
     */
    public function terhubung(): array
    {
        $hasil = [];

        foreach ($this->daftarBaris as $baris) {
            foreach ($baris->{$this->relasi()} as $kolom) {
                $bobot = $this->kolomBobot() === null ? null : (float) $kolom->pivot->{$this->kolomBobot()};
                $hasil[self::kunci($baris->getKey(), $kolom->getKey())] = $bobot;
            }
        }

        return $hasil;
    }

    /**
     * Total bobot CPMK per mata kuliah dari data tersimpan. Totalnya seharusnya 100 (bagian 6).
     *
     * @return array<string, float> id mata kuliah => total bobot_cpmk
     */
    public function totalBobotPerMataKuliah(): array
    {
        $total = array_fill_keys($this->daftarKolom->modelKeys(), 0.0);

        foreach ($this->terhubung() as $kunci => $bobot) {
            $idKolom = explode('|', $kunci)[1];
            $total[$idKolom] += $bobot;
        }

        return $total;
    }

    /**
     * Apakah kunci sel berasal dari baris dan kolom matriks ini, artinya kedua data ada di
     * kurikulum yang sama (bagian 6 spesifikasi).
     */
    public function kunciValid(string $kunci): bool
    {
        $bagian = explode('|', $kunci);

        return count($bagian) === 2
            && in_array($bagian[0], $this->daftarBaris->modelKeys(), true)
            && in_array($bagian[1], $this->daftarKolom->modelKeys(), true);
    }

    /**
     * Pasangan CPMK–MK yang akan dilepas padahal sudah memiliki Sub-CPMK. Pasangan seperti itu
     * tidak boleh dihapus (KD-20).
     *
     * @param  list<string>  $kunciDipilih
     * @return list<string> label pasangan, mis. "CPMK021 · INF101"
     */
    public function pasanganTerkunci(array $kunciDipilih): array
    {
        if ($this->jenis !== 'cpmk-mk') {
            return [];
        }

        $kunciDilepas = array_diff(array_keys($this->terhubung()), $kunciDipilih);

        return PemetaanCpmkMk::with(['cpmk', 'mataKuliah'])
            ->whereIn('mata_kuliah_id', $this->daftarKolom->modelKeys())
            ->has('subCpmk')
            ->get()
            ->filter(fn (PemetaanCpmkMk $pemetaan) => in_array(self::kunci($pemetaan->cpmk_id, $pemetaan->mata_kuliah_id), $kunciDilepas, true))
            ->map(fn (PemetaanCpmkMk $pemetaan) => $pemetaan->label())
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Menyimpan seluruh matriks: pasangan yang dipilih dihubungkan (dengan bobotnya) dan
     * pasangan lain dilepas.
     *
     * @param  list<string>  $kunciDipilih
     * @param  array<string, string|float>  $bobot  kunci sel => bobot, untuk pemetaan berbobot
     */
    public function simpan(array $kunciDipilih, array $bobot): void
    {
        DB::transaction(function () use ($kunciDipilih, $bobot) {
            foreach ($this->daftarBaris as $baris) {
                $dataPivot = [];

                foreach ($this->daftarKolom as $kolom) {
                    $kunci = self::kunci($baris->getKey(), $kolom->getKey());

                    if (in_array($kunci, $kunciDipilih, true)) {
                        $dataPivot[$kolom->getKey()] = $this->kolomBobot() === null
                            ? []
                            : [$this->kolomBobot() => $bobot[$kunci]];
                    }
                }

                // sync() menambah pasangan baru, memperbarui bobot pasangan lama, dan melepas sisanya.
                $baris->{$this->relasi()}()->sync($dataPivot);
            }
        });
    }
}
