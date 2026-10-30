<?php

namespace App\Services;

use App\Models\KelasKuliah;
use App\Models\PemetaanCpmkMk;
use App\Models\RencanaAsesmen;
use App\Models\SubCpmk;
use App\Support\Angka;
use Illuminate\Database\Eloquent\Collection;

/**
 * Matriks bobot rencana asesmen satu kelas, seperti bagian Penilaian RPS APTIKOM (bagian 7.6.7
 * spesifikasi):
 * - baris = Sub-CPMK milik MK kelas ini, dikelompokkan per CPMK;
 * - kolom = asesmen;
 * - sel = bobot_kontribusi dalam % nilai akhir MK.
 *
 * Class ini juga menyusun peringatan bobot yang tidak memblokir (bagian 6). Perhitungan capaian
 * sendiri ada di CapaianService.
 */
class MatriksBobotKelas
{
    /** @var Collection<int, PemetaanCpmkMk> pasangan CPMK–MK beserta Sub-CPMK-nya */
    public readonly Collection $daftarPemetaan;

    /** @var Collection<int, RencanaAsesmen> */
    public readonly Collection $daftarAsesmen;

    public function __construct(public readonly KelasKuliah $kelas)
    {
        $this->daftarPemetaan = PemetaanCpmkMk::with(['cpmk', 'subCpmk.metodePenilaian'])
            ->where('mata_kuliah_id', $kelas->mata_kuliah_id)
            ->get()
            ->sortBy(fn (PemetaanCpmkMk $pemetaan) => $pemetaan->cpmk->kode_cpmk)
            ->values();

        $this->daftarAsesmen = $kelas->rencanaAsesmen()
            ->with(['metodePenilaian', 'subCpmk'])
            ->orderBy('tanggal_asesmen')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Bobot kontribusi asesmen ke Sub-CPMK, atau null jika asesmen itu tidak mengukur Sub-CPMK ini.
     */
    public function bobot(RencanaAsesmen $asesmen, SubCpmk $subCpmk): ?float
    {
        $subCpmkAsesmen = $asesmen->subCpmk->firstWhere('sub_cpmk_id', $subCpmk->sub_cpmk_id);

        return $subCpmkAsesmen === null ? null : (float) $subCpmkAsesmen->pivot->bobot_kontribusi;
    }

    /**
     * Σ bobot kontribusi ke satu Sub-CPMK di kelas ini. Jika rencana asesmen sesuai RPS,
     * hasilnya sama dengan bobot_sub_cpmk (bagian 5.1).
     */
    public function totalSubCpmk(SubCpmk $subCpmk): float
    {
        return $this->daftarAsesmen->sum(fn (RencanaAsesmen $asesmen) => $this->bobot($asesmen, $subCpmk) ?? 0);
    }

    /**
     * Σ bobot kontribusi satu asesmen. Validasi form menjamin nilainya sama dengan bobot_asesmen.
     */
    public function totalAsesmen(RencanaAsesmen $asesmen): float
    {
        return (float) $asesmen->subCpmk->sum('pivot.bobot_kontribusi');
    }

    /**
     * Σ bobot asesmen kelas ini; seharusnya 100.
     */
    public function totalBobotAsesmen(): float
    {
        return (float) $this->daftarAsesmen->sum('bobot_asesmen');
    }

    /**
     * Peringatan yang tidak memblokir (bagian 6 spesifikasi).
     *
     * @return list<string>
     */
    public function peringatan(): array
    {
        $daftarPeringatan = [];

        if ($this->daftarAsesmen->isNotEmpty() && ! Angka::sama($this->totalBobotAsesmen(), 100)) {
            $daftarPeringatan[] = 'Total bobot asesmen kelas ini '.Angka::ringkas($this->totalBobotAsesmen()).'%, seharusnya 100%.';
        }

        $subCpmkPerId = $this->daftarPemetaan->flatMap(fn (PemetaanCpmkMk $pemetaan) => $pemetaan->subCpmk)->keyBy('sub_cpmk_id');

        // Bobot kelas dibandingkan dengan target Sub-CPMK di kurikulum (KD-08).
        foreach ($subCpmkPerId as $subCpmk) {
            $total = $this->totalSubCpmk($subCpmk);

            if (! Angka::sama($total, $subCpmk->bobot_sub_cpmk)) {
                $daftarPeringatan[] = "Bobot kontribusi ke {$subCpmk->kode_sub_cpmk} di kelas ini "
                    .Angka::ringkas($total).'%, belum sama dengan targetnya '.Angka::ringkas($subCpmk->bobot_sub_cpmk).'%.';
            }
        }

        // Metode asesmen harus termasuk metode yang direncanakan di rincian penilaian Sub-CPMK.
        foreach ($this->daftarAsesmen as $asesmen) {
            foreach ($asesmen->subCpmk as $subCpmkAsesmen) {
                $subCpmk = $subCpmkPerId->get($subCpmkAsesmen->sub_cpmk_id);

                if ($subCpmk !== null && ! $subCpmk->metodePenilaian->contains('metode_penilaian_id', $asesmen->metode_penilaian_id)) {
                    $daftarPeringatan[] = "Metode {$asesmen->metodePenilaian->nama_metode} pada asesmen \"{$asesmen->nama_asesmen}\" "
                        ."tidak termasuk metode yang direncanakan untuk {$subCpmk->kode_sub_cpmk}.";
                }
            }
        }

        return $daftarPeringatan;
    }
}
