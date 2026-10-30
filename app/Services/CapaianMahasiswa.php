<?php

namespace App\Services;

use App\Models\Cpl;
use App\Models\Mahasiswa;
use App\Models\PemetaanCpmkMk;
use App\Models\PeriodeAkademik;
use App\Models\PesertaKelas;
use Illuminate\Support\Collection;

/**
 * Menyusun isi halaman Capaian Pembelajaran Mahasiswa (bagian 7.6.10 spesifikasi, KF-15).
 * Data yang dimuat hanya milik satu mahasiswa. Semua rumus tetap di CapaianService.
 */
class CapaianMahasiswa
{
    public function __construct(private CapaianService $capaian) {}

    /**
     * @return array{
     *     pilihanPeriode: array<string, string>, periode: ?PeriodeAkademik,
     *     pilihanMataKuliah: array<string, string>, peserta: ?PesertaKelas,
     *     mataKuliah: ?array, cplKumulatif: list<array>
     * }
     */
    public function susun(Mahasiswa $mahasiswa, mixed $periodeId, mixed $mataKuliahId): array
    {
        $daftarPeserta = PesertaKelas::with([...CapaianService::RELASI_PESERTA, 'kelasKuliah.mataKuliah.kurikulum', 'kelasKuliah.rencanaAsesmen.metodePenilaian'])
            ->where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->get();

        // Pilihan periode dan mata kuliah hanya dari kelas yang diikuti mahasiswa ini.
        $daftarPeriode = $daftarPeserta->pluck('kelasKuliah.periodeAkademik')
            ->unique('periode_akademik_id')
            ->sortByDesc(fn (PeriodeAkademik $periode) => $periode->urutan())
            ->values();
        $periode = $daftarPeriode->firstWhere('periode_akademik_id', $periodeId) ?? $daftarPeriode->first();

        $pesertaPeriode = $daftarPeserta
            ->filter(fn (PesertaKelas $peserta) => $peserta->kelasKuliah->periode_akademik_id === $periode?->periode_akademik_id)
            ->sortBy('kelasKuliah.mataKuliah.kode_mata_kuliah')
            ->values();
        $peserta = $pesertaPeriode->first(fn (PesertaKelas $peserta) => $peserta->kelasKuliah->mata_kuliah_id === $mataKuliahId)
            ?? $pesertaPeriode->first();

        // CPL kumulatif dari semua MK yang sudah diikuti; MK yang diulang memakai kelas terbaru
        // yang sudah bernilai (bagian 5.4).
        $cplKumulatif = $this->capaian->hitungCapaianCpl($this->capaian->pilihKelasPerMataKuliah($daftarPeserta));
        $idKurikulum = $daftarPeserta->pluck('kelasKuliah.mataKuliah.kurikulum_id')->unique()->values()->all();

        return [
            'pilihanPeriode' => $daftarPeriode->mapWithKeys(fn (PeriodeAkademik $periode) => [$periode->periode_akademik_id => $periode->nama()])->all(),
            'periode' => $periode,
            'pilihanMataKuliah' => $pesertaPeriode->mapWithKeys(fn (PesertaKelas $peserta) => [
                $peserta->kelasKuliah->mata_kuliah_id => $peserta->kelasKuliah->mataKuliah->kode_mata_kuliah.' '.$peserta->kelasKuliah->mataKuliah->nama_mata_kuliah,
            ])->all(),
            'peserta' => $peserta,
            'mataKuliah' => $peserta === null ? null : $this->rincianMataKuliah($peserta, $cplKumulatif),
            'cplKumulatif' => $this->cplPerKurikulum($idKurikulum, $cplKumulatif),
        ];
    }

    /**
     * Nilai asesmen serta capaian Sub-CPMK, CPMK, dan CPL dari satu kelas yang diikuti.
     *
     * @param  array<string, array{nilai: float, bobot: float}>  $cplKumulatif
     */
    private function rincianMataKuliah(PesertaKelas $peserta, array $cplKumulatif): array
    {
        $kelas = $peserta->kelasKuliah;
        $hasil = $this->capaian->hitungCapaianPeserta($peserta);
        // Kontribusi MK ini ke CPL: CPL dihitung dari kelas ini saja.
        $cplMataKuliah = $this->capaian->hitungCapaianCpl([$peserta]);
        $daftarPemetaan = PemetaanCpmkMk::with(['cpmk.cpl', 'subCpmk'])
            ->where('mata_kuliah_id', $kelas->mata_kuliah_id)
            ->get()
            ->sortBy('cpmk.kode_cpmk');

        $nilaiPerAsesmen = $peserta->nilaiAsesmen->keyBy('rencana_asesmen_id');
        $daftarNilai = $kelas->rencanaAsesmen->sortBy([['tanggal_asesmen', 'asc'], ['created_at', 'asc']])->values()
            ->map(fn ($asesmen) => [
                'nama' => $asesmen->nama_asesmen,
                'metode' => $asesmen->metodePenilaian->nama_metode,
                'nilai' => $nilaiPerAsesmen->get($asesmen->rencana_asesmen_id)?->nilai,
                'nilai_maksimum' => $asesmen->nilai_maksimum,
            ]);

        $baris = fn (string $kode, string $deskripsi, ?float $capaian) => [
            'kode' => $kode,
            'deskripsi' => $deskripsi,
            'capaian' => $capaian,
            'lulus' => $this->capaian->lulus($capaian),
        ];

        $subCpmk = $daftarPemetaan->flatMap(fn (PemetaanCpmkMk $pemetaan) => $pemetaan->subCpmk->sortBy('kode_sub_cpmk')
            ->map(fn ($sub) => $baris($sub->kode_sub_cpmk, $sub->deskripsi_sub_cpmk, $hasil['sub_cpmk'][$sub->sub_cpmk_id]['nilai'] ?? null)))->values();
        $cpmk = $daftarPemetaan->map(fn (PemetaanCpmkMk $pemetaan) => $baris(
            $pemetaan->cpmk->kode_cpmk, $pemetaan->cpmk->deskripsi_cpmk, $hasil['cpmk'][$pemetaan->pemetaan_cpmk_mk_id]['nilai'] ?? null,
        ))->values();
        $cpl = $daftarPemetaan->pluck('cpmk.cpl')->unique('cpl_id')->sortBy('kode_cpl')
            ->map(fn (Cpl $cpl) => $baris($cpl->kode_cpl, $cpl->deskripsi_cpl, $cplMataKuliah[$cpl->cpl_id]['nilai'] ?? null))->values();

        // Rata-rata CPL kumulatif di kurikulum MK ini (CPL dari kurikulum lain tidak digabung).
        $idCplKurikulum = Cpl::where('kurikulum_id', $kelas->mataKuliah->kurikulum_id)->pluck('cpl_id')->all();
        $nilaiCplKumulatif = collect($cplKumulatif)->only($idCplKurikulum)->pluck('nilai');

        return [
            'kelas' => $kelas,
            'nilaiAsesmen' => $daftarNilai,
            'subCpmk' => $subCpmk,
            'cpmk' => $cpmk,
            'cpl' => $cpl,
            'ringkasan' => [
                'sub_cpmk' => $this->rataRata($subCpmk),
                'cpmk' => $this->rataRata($cpmk),
                'cpl_kumulatif' => $nilaiCplKumulatif->isEmpty() ? null : $nilaiCplKumulatif->avg(),
            ],
        ];
    }

    /**
     * CPL kumulatif dipisah per kurikulum (bagian 5.4): satu kelompok untuk setiap kurikulum yang
     * mata kuliahnya pernah diikuti, berisi semua CPL kurikulum itu.
     *
     * @param  list<string>  $idKurikulum
     * @param  array<string, array{nilai: float, bobot: float}>  $cplKumulatif
     */
    private function cplPerKurikulum(array $idKurikulum, array $cplKumulatif): array
    {
        return Cpl::with('kurikulum')
            ->whereIn('kurikulum_id', $idKurikulum)
            ->orderBy('kode_cpl')
            ->get()
            ->groupBy('kurikulum_id')
            ->map(fn (Collection $daftarCpl) => [
                'kurikulum' => $daftarCpl->first()->kurikulum,
                'cpl' => $daftarCpl->map(fn (Cpl $cpl) => [
                    'kode' => $cpl->kode_cpl,
                    'deskripsi' => $cpl->deskripsi_cpl,
                    'capaian' => $cplKumulatif[$cpl->cpl_id]['nilai'] ?? null,
                    'lulus' => $this->capaian->lulus($cplKumulatif[$cpl->cpl_id]['nilai'] ?? null),
                ])->values(),
            ])
            ->sortByDesc('kurikulum.tahun_berlaku')
            ->values()
            ->all();
    }

    private function rataRata(Collection $baris): ?float
    {
        $nilai = $baris->pluck('capaian')->filter(fn ($capaian) => $capaian !== null);

        return $nilai->isEmpty() ? null : $nilai->avg();
    }
}
