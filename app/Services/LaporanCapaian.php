<?php

namespace App\Services;

use App\Models\Cpl;
use App\Models\Kurikulum;
use App\Models\PemetaanCpmkMk;
use App\Models\PesertaKelas;
use App\Models\SubCpmk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Menyusun isi Laporan Capaian (bagian 7.6.9 spesifikasi) dan Ringkasan Capaian CPL di Dashboard
 * Koprodi (7.6.2). Class ini hanya memilih cakupan data, label, dan urutan baris. Semua rumus
 * capaian dan status tetap di CapaianService.
 */
class LaporanCapaian
{
    public const TAB = ['cpl' => 'CPL', 'cpmk' => 'CPMK', 'sub-cpmk' => 'Sub-CPMK'];

    public function __construct(private CapaianService $capaian) {}

    /**
     * Peserta kelas dalam cakupan filter, dimuat bersama relasi yang dibutuhkan perhitungan.
     *
     * Kunci $filter yang dipakai: kurikulum_id (wajib), periode_id, angkatan, mata_kuliah_id,
     * kelas_id, mahasiswa_id, dan dosen_id (laporan Dosen hanya dari kelas yang ia ampu, KD-46).
     *
     * @param  array<string, mixed>  $filter
     * @return Collection<int, PesertaKelas>
     */
    public function pesertaDalamCakupan(array $filter): Collection
    {
        return PesertaKelas::with(CapaianService::RELASI_PESERTA)
            ->whereHas('kelasKuliah', fn (Builder $query) => $query
                ->whereHas('mataKuliah', fn (Builder $query) => $query->where('kurikulum_id', $filter['kurikulum_id']))
                ->when($filter['periode_id'] ?? null, fn (Builder $query, $periodeId) => $query->where('periode_akademik_id', $periodeId))
                ->when($filter['mata_kuliah_id'] ?? null, fn (Builder $query, $mataKuliahId) => $query->where('mata_kuliah_id', $mataKuliahId))
                ->when($filter['kelas_id'] ?? null, fn (Builder $query, $kelasId) => $query->whereKey($kelasId))
                ->when($filter['dosen_id'] ?? null, fn (Builder $query, $dosenId) => $query->whereHas('dosen', fn (Builder $query) => $query->whereKey($dosenId))))
            ->when($filter['angkatan'] ?? null, fn (Builder $query, $angkatan) => $query->whereHas('mahasiswa', fn (Builder $query) => $query->where('angkatan', $angkatan)))
            ->when($filter['mahasiswa_id'] ?? null, fn (Builder $query, $mahasiswaId) => $query->where('mahasiswa_id', $mahasiswaId))
            ->get();
    }

    /**
     * Baris laporan untuk satu tab beserta ringkasannya.
     *
     * - CPL: semua CPL kurikulum (bisa disaring satu CPL).
     * - CPMK: setiap pasangan CPMK–MK dari mata kuliah dalam cakupan (KD-25).
     * - Sub-CPMK: setiap Sub-CPMK dari mata kuliah dalam cakupan.
     * Item tanpa nilai tetap tampil sebagai "Belum ada nilai".
     *
     * @param  array<string, mixed>  $filter
     * @return array{baris: list<array{kode: string, keterangan: string, rekap: array}>, ringkasan: array}
     */
    public function susun(string $tab, array $filter): array
    {
        $daftarPeserta = $this->pesertaDalamCakupan($filter);
        $capaianPerItem = $this->capaian->capaianPerItem($daftarPeserta);
        // Label cukup kode jika laporan sudah disaring satu MK (KD-25).
        $satuMataKuliah = ! empty($filter['mata_kuliah_id']);

        $baris = match ($tab) {
            'cpl' => $this->barisCpl($filter, $capaianPerItem['cpl'], empty($filter['dosen_id']) ? null : $this->idMataKuliah($daftarPeserta)),
            'cpmk' => $this->barisCpmk($filter, $daftarPeserta, $capaianPerItem['cpmk'], $satuMataKuliah),
            'sub-cpmk' => $this->barisSubCpmk($filter, $daftarPeserta, $capaianPerItem['sub_cpmk'], $satuMataKuliah),
        };

        return ['baris' => $baris, 'ringkasan' => $this->ringkasan($baris)];
    }

    /**
     * Ringkasan Capaian CPL di Dashboard Koprodi: selalu kumulatif semua periode, mengikuti
     * kurikulum dan angkatan (bagian 5.3, KD-23). Kategori ketiga "Belum ada nilai" untuk CPL
     * yang belum punya nilai (KD-40).
     *
     * @return array{tercapai: int, tidak_tercapai: int, belum_ada_nilai: int, total: int}
     */
    public function ringkasanCpl(Kurikulum $kurikulum, ?int $angkatan): array
    {
        $baris = $this->barisCpl(
            ['kurikulum_id' => $kurikulum->kurikulum_id],
            $this->capaian->capaianPerItem($this->pesertaDalamCakupan(['kurikulum_id' => $kurikulum->kurikulum_id, 'angkatan' => $angkatan]))['cpl'],
        );

        $hasil = ['tercapai' => 0, 'tidak_tercapai' => 0, 'belum_ada_nilai' => 0, 'total' => count($baris)];

        foreach ($baris as $satuBaris) {
            $kategori = match ($satuBaris['rekap']['tercapai']) {
                true => 'tercapai',
                false => 'tidak_tercapai',
                null => 'belum_ada_nilai',
            };
            $hasil[$kategori]++;
        }

        return $hasil;
    }

    /**
     * Baris CPL. Koprodi melihat semua CPL kurikulum (termasuk yang belum punya nilai); laporan
     * Dosen hanya CPL yang didukung MK yang ia ampu ($idMataKuliah), KD-46.
     *
     * @param  array<string, array<string, float>>  $capaian  cpl_id => [mahasiswa_id => capaian]
     * @param  list<string>|null  $idMataKuliah
     */
    private function barisCpl(array $filter, array $capaian, ?array $idMataKuliah = null): array
    {
        return Cpl::where('kurikulum_id', $filter['kurikulum_id'])
            ->when($idMataKuliah !== null, fn (Builder $query) => $query->whereHas('cpmk.pemetaanCpmkMk', fn (Builder $query) => $query->whereIn('mata_kuliah_id', $idMataKuliah)))
            ->when($filter['cpl_id'] ?? null, fn (Builder $query, $cplId) => $query->whereKey($cplId))
            ->orderBy('kode_cpl')
            ->get()
            ->map(fn (Cpl $cpl) => [
                'kode' => $cpl->kode_cpl,
                'keterangan' => $cpl->deskripsi_cpl,
                'rekap' => $this->capaian->rekap($capaian[$cpl->cpl_id] ?? []),
            ])
            ->all();
    }

    /**
     * @param  array<string, array<string, float>>  $capaian  pemetaan_cpmk_mk_id => [mahasiswa_id => capaian]
     */
    private function barisCpmk(array $filter, Collection $daftarPeserta, array $capaian, bool $satuMataKuliah): array
    {
        return PemetaanCpmkMk::with(['cpmk', 'mataKuliah'])
            ->whereIn('mata_kuliah_id', $this->idMataKuliah($daftarPeserta))
            ->when($filter['cpl_id'] ?? null, fn (Builder $query, $cplId) => $query->whereHas('cpmk', fn (Builder $query) => $query->where('cpl_id', $cplId)))
            ->when($filter['cpmk_id'] ?? null, fn (Builder $query, $cpmkId) => $query->where('cpmk_id', $cpmkId))
            ->get()
            // Urut kode CPMK lalu kode MK, supaya CPMK yang sama letaknya berdekatan (KD-25).
            ->sortBy([['cpmk.kode_cpmk', 'asc'], ['mataKuliah.kode_mata_kuliah', 'asc']])
            ->map(fn (PemetaanCpmkMk $pemetaan) => [
                'kode' => $satuMataKuliah ? $pemetaan->cpmk->kode_cpmk : $pemetaan->label(),
                'keterangan' => $pemetaan->cpmk->deskripsi_cpmk,
                'rekap' => $this->capaian->rekap($capaian[$pemetaan->pemetaan_cpmk_mk_id] ?? []),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array<string, float>>  $capaian  sub_cpmk_id => [mahasiswa_id => capaian]
     */
    private function barisSubCpmk(array $filter, Collection $daftarPeserta, array $capaian, bool $satuMataKuliah): array
    {
        return SubCpmk::with(['pemetaanCpmkMk.cpmk', 'pemetaanCpmkMk.mataKuliah'])
            ->whereHas('pemetaanCpmkMk', fn (Builder $query) => $query
                ->whereIn('mata_kuliah_id', $this->idMataKuliah($daftarPeserta))
                ->when($filter['cpmk_id'] ?? null, fn (Builder $query, $cpmkId) => $query->where('cpmk_id', $cpmkId))
                ->when($filter['cpl_id'] ?? null, fn (Builder $query, $cplId) => $query->whereHas('cpmk', fn (Builder $query) => $query->where('cpl_id', $cplId))))
            ->when($filter['sub_cpmk_id'] ?? null, fn (Builder $query, $subCpmkId) => $query->whereKey($subCpmkId))
            ->get()
            ->sortBy([['kode_sub_cpmk', 'asc'], ['pemetaanCpmkMk.mataKuliah.kode_mata_kuliah', 'asc']])
            ->map(fn (SubCpmk $subCpmk) => [
                'kode' => $satuMataKuliah
                    ? $subCpmk->kode_sub_cpmk
                    : $subCpmk->kode_sub_cpmk.' · '.$subCpmk->pemetaanCpmkMk->mataKuliah->kode_mata_kuliah,
                'keterangan' => $subCpmk->deskripsi_sub_cpmk,
                'rekap' => $this->capaian->rekap($capaian[$subCpmk->sub_cpmk_id] ?? []),
            ])
            ->values()
            ->all();
    }

    /**
     * Empat kartu ringkasan laporan: rata-rata capaian, capaian tertinggi dan terendah (beserta
     * kodenya), dan jumlah item. Dihitung dari rata-rata tiap item yang sudah punya nilai.
     *
     * @param  list<array{kode: string, rekap: array}>  $baris
     * @return array{rata_rata: ?float, tertinggi: ?array, terendah: ?array, jumlah_item: int}
     */
    private function ringkasan(array $baris): array
    {
        $bernilai = collect($baris)->filter(fn (array $satuBaris) => $satuBaris['rekap']['rata_rata'] !== null);

        return [
            'rata_rata' => $bernilai->isEmpty() ? null : $bernilai->avg('rekap.rata_rata'),
            'tertinggi' => $bernilai->sortByDesc('rekap.rata_rata')->first(),
            'terendah' => $bernilai->sortBy('rekap.rata_rata')->first(),
            'jumlah_item' => count($baris),
        ];
    }

    /**
     * @return list<string>
     */
    private function idMataKuliah(Collection $daftarPeserta): array
    {
        return $daftarPeserta->pluck('kelasKuliah.mata_kuliah_id')->unique()->values()->all();
    }
}
