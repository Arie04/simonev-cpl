<?php

namespace App\Services;

use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeAkademik;
use App\Models\SubCpmk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Pilihan dan isian kartu filter Laporan Capaian (bagian 7.6.9 spesifikasi): kurikulum, periode,
 * angkatan, mata kuliah, kelas, mahasiswa, CPL, CPMK, dan Sub-CPMK.
 *
 * Untuk Dosen, semua pilihan hanya berasal dari kelas yang ia ampu (KD-46). Nilai dari alamat
 * yang tidak termasuk pilihan diabaikan, jadi mengetik id lain di alamat tidak membuka data lain.
 */
class FilterLaporan
{
    /** @var array<string, mixed> isian terpilih, dipakai LaporanCapaian */
    public array $filter = [];

    /** @var array<string, array<string, string>> pilihan dropdown: nama => [nilai => label] */
    public array $pilihan = [];

    public ?Kurikulum $kurikulum = null;

    public ?PeriodeAkademik $periode = null;

    public function __construct(Request $request, ?Dosen $dosen)
    {
        $daftarKelas = KelasKuliah::with(['mataKuliah.kurikulum', 'periodeAkademik'])
            ->when($dosen, fn (Builder $query) => $query->whereHas('dosen', fn (Builder $query) => $query->whereKey($dosen->dosen_id)))
            ->get();

        // Kurikulum: Koprodi melihat semua; Dosen hanya kurikulum MK yang ia ampu. Bawaan: terbaru.
        $daftarKurikulum = $dosen
            ? $daftarKelas->pluck('mataKuliah.kurikulum')->unique('kurikulum_id')->sortByDesc('tahun_berlaku')->values()
            : Kurikulum::pilihan();
        $this->kurikulum = $daftarKurikulum->firstWhere('kurikulum_id', $request->query('kurikulum')) ?? $daftarKurikulum->first();
        $this->pilihan['kurikulum'] = $daftarKurikulum->pluck('nama_kurikulum', 'kurikulum_id')->all();

        if ($this->kurikulum === null) {
            return;
        }

        $kelasKurikulum = $daftarKelas->where('mataKuliah.kurikulum_id', $this->kurikulum->kurikulum_id);

        // Periode: hanya periode yang punya kelas di kurikulum ini.
        $daftarPeriode = $kelasKurikulum->pluck('periodeAkademik')->unique('periode_akademik_id')
            ->sortBy([['tahun_akademik', 'desc'], ['semester_periode', 'desc']]);
        $this->periode = $daftarPeriode->firstWhere('periode_akademik_id', $request->query('periode'));
        $this->pilihan['periode'] = $daftarPeriode->mapWithKeys(fn (PeriodeAkademik $periode) => [$periode->periode_akademik_id => $periode->nama()])->all();
        $kelasPeriode = $this->periode ? $kelasKurikulum->where('periode_akademik_id', $this->periode->periode_akademik_id) : $kelasKurikulum;

        // Mata kuliah dan kelas.
        $daftarMataKuliah = $kelasPeriode->pluck('mataKuliah')->unique('mata_kuliah_id')->sortBy('kode_mata_kuliah');
        $mataKuliah = $daftarMataKuliah->firstWhere('mata_kuliah_id', $request->query('mata_kuliah'));
        $this->pilihan['mata_kuliah'] = $daftarMataKuliah->mapWithKeys(fn (MataKuliah $mataKuliah) => [$mataKuliah->mata_kuliah_id => "{$mataKuliah->kode_mata_kuliah} {$mataKuliah->nama_mata_kuliah}"])->all();

        $daftarKelasMataKuliah = $mataKuliah ? $kelasPeriode->where('mata_kuliah_id', $mataKuliah->mata_kuliah_id) : collect();
        $kelas = $daftarKelasMataKuliah->firstWhere('kelas_kuliah_id', $request->query('kelas'));
        $this->pilihan['kelas'] = $daftarKelasMataKuliah
            ->sortBy([['periodeAkademik.tahun_akademik', 'desc'], ['nama_kelas', 'asc']])
            ->mapWithKeys(fn (KelasKuliah $kelas) => [$kelas->kelas_kuliah_id => "Kelas {$kelas->nama_kelas} · {$kelas->periodeAkademik->nama()}"])
            ->all();

        // Angkatan dan mahasiswa: hanya mahasiswa yang mengikuti kelas dalam cakupan.
        $idKelasCakupan = ($kelas ? collect([$kelas]) : ($mataKuliah ? $daftarKelasMataKuliah : $kelasPeriode))->pluck('kelas_kuliah_id')->all();
        $daftarMahasiswa = Mahasiswa::whereHas('pesertaKelas', fn (Builder $query) => $query->whereIn('kelas_kuliah_id', $idKelasCakupan))
            ->orderBy('nim')
            ->get();
        $angkatan = $daftarMahasiswa->pluck('angkatan')->contains((int) $request->query('angkatan')) ? (int) $request->query('angkatan') : null;
        $this->pilihan['angkatan'] = $daftarMahasiswa->pluck('angkatan')->unique()->sortDesc()->mapWithKeys(fn (int $tahun) => [$tahun => (string) $tahun])->all();

        $mahasiswaAngkatan = $angkatan ? $daftarMahasiswa->where('angkatan', $angkatan) : $daftarMahasiswa;
        $mahasiswa = $mahasiswaAngkatan->firstWhere('mahasiswa_id', $request->query('mahasiswa'));
        $this->pilihan['mahasiswa'] = $mahasiswaAngkatan->mapWithKeys(fn (Mahasiswa $mahasiswa) => [$mahasiswa->mahasiswa_id => "{$mahasiswa->nim} – {$mahasiswa->nama}"])->all();

        // CPL, CPMK, dan Sub-CPMK di kurikulum ini. Untuk Dosen hanya yang terhubung ke MK yang
        // ia ampu, supaya CPMK mata kuliah dosen lain tidak ikut tampil.
        $idMataKuliahDiampu = $kelasKurikulum->pluck('mata_kuliah_id')->unique()->values()->all();
        $daftarCpl = Cpl::where('kurikulum_id', $this->kurikulum->kurikulum_id)
            ->when($dosen, fn (Builder $query) => $query->whereHas('cpmk.pemetaanCpmkMk', fn (Builder $query) => $query->whereIn('mata_kuliah_id', $idMataKuliahDiampu)))
            ->orderBy('kode_cpl')
            ->get();
        $cpl = $daftarCpl->firstWhere('cpl_id', $request->query('cpl'));
        $this->pilihan['cpl'] = $daftarCpl->pluck('kode_cpl', 'cpl_id')->all();

        $daftarCpmk = Cpmk::whereIn('cpl_id', $cpl ? [$cpl->cpl_id] : $daftarCpl->modelKeys())
            ->when($dosen, fn (Builder $query) => $query->whereHas('pemetaanCpmkMk', fn (Builder $query) => $query->whereIn('mata_kuliah_id', $idMataKuliahDiampu)))
            ->orderBy('kode_cpmk')
            ->get();
        $cpmk = $daftarCpmk->firstWhere('cpmk_id', $request->query('cpmk'));
        $this->pilihan['cpmk'] = $daftarCpmk->pluck('kode_cpmk', 'cpmk_id')->all();

        $daftarSubCpmk = $this->daftarSubCpmk($daftarMataKuliah->pluck('mata_kuliah_id')->all(), $mataKuliah, $cpmk, $daftarCpmk);
        $subCpmk = $daftarSubCpmk->firstWhere('sub_cpmk_id', $request->query('sub_cpmk'));
        $this->pilihan['sub_cpmk'] = $daftarSubCpmk->mapWithKeys(fn (SubCpmk $subCpmk) => [
            $subCpmk->sub_cpmk_id => $subCpmk->kode_sub_cpmk.' · '.$subCpmk->pemetaanCpmkMk->mataKuliah->kode_mata_kuliah,
        ])->all();

        $this->filter = [
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'periode_id' => $this->periode?->periode_akademik_id,
            'angkatan' => $angkatan,
            'mata_kuliah_id' => $mataKuliah?->mata_kuliah_id,
            'kelas_id' => $kelas?->kelas_kuliah_id,
            'mahasiswa_id' => $mahasiswa?->mahasiswa_id,
            'cpl_id' => $cpl?->cpl_id,
            'cpmk_id' => $cpmk?->cpmk_id,
            'sub_cpmk_id' => $subCpmk?->sub_cpmk_id,
            'dosen_id' => $dosen?->dosen_id,
        ];
    }

    /**
     * Nilai terpilih untuk dropdown di view, mis. terpilih('kelas').
     */
    public function terpilih(string $nama): ?string
    {
        $kunci = match ($nama) {
            'kurikulum' => 'kurikulum_id',
            'periode' => 'periode_id',
            'mata_kuliah' => 'mata_kuliah_id',
            'kelas' => 'kelas_id',
            'mahasiswa' => 'mahasiswa_id',
            'cpl' => 'cpl_id',
            'cpmk' => 'cpmk_id',
            'sub_cpmk' => 'sub_cpmk_id',
            default => $nama,
        };

        $nilai = $this->filter[$kunci] ?? null;

        return $nilai === null ? null : (string) $nilai;
    }

    /**
     * @param  list<string>  $idMataKuliah
     * @return Collection<int, SubCpmk>
     */
    private function daftarSubCpmk(array $idMataKuliah, ?MataKuliah $mataKuliah, ?Cpmk $cpmk, Collection $daftarCpmk): Collection
    {
        return SubCpmk::with('pemetaanCpmkMk.mataKuliah')
            ->whereHas('pemetaanCpmkMk', fn (Builder $query) => $query
                ->whereIn('mata_kuliah_id', $mataKuliah ? [$mataKuliah->mata_kuliah_id] : $idMataKuliah)
                ->whereIn('cpmk_id', $cpmk ? [$cpmk->cpmk_id] : $daftarCpmk->modelKeys()))
            ->get()
            ->sortBy([['kode_sub_cpmk', 'asc'], ['pemetaanCpmkMk.mataKuliah.kode_mata_kuliah', 'asc']])
            ->values();
    }
}
