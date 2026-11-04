<?php

namespace App\Services;

use App\Models\Cpl;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\PemetaanCpmkMk;
use App\Models\PeriodeAkademik;
use App\Models\PesertaKelas;
use Illuminate\Support\Collection;

/**
 * Menyusun isi Dashboard Mahasiswa (KD-74): ringkasan CPL kumulatif, CPMK penyusun setiap CPL,
 * perkembangan CPL per periode, dan daftar mata kuliah yang diikuti. Data yang dimuat hanya milik
 * satu mahasiswa. Semua rumus tetap di CapaianService; class ini hanya memanggil dan menyusunnya.
 */
class DashboardMahasiswa
{
    public function __construct(private CapaianService $capaian) {}

    /**
     * @return array{
     *     pilihanKurikulum: array<string, string>, kurikulum: ?Kurikulum, ringkasan: array,
     *     daftarCpl: Collection<int, array>, perkembangan: ?array, daftarMataKuliah: Collection<int, array>
     * }
     */
    public function susun(Mahasiswa $mahasiswa, mixed $kurikulumId): array
    {
        $daftarPeserta = PesertaKelas::with([...CapaianService::RELASI_PESERTA, 'kelasKuliah.mataKuliah.kurikulum'])
            ->where('mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->get();

        // Pilihan kurikulum hanya dari mata kuliah yang diikuti. Bawaannya kurikulum terbaru,
        // sama seperti Dashboard Koprodi (bagian 7.6.2).
        $daftarKurikulum = $daftarPeserta->pluck('kelasKuliah.mataKuliah.kurikulum')
            ->unique('kurikulum_id')
            ->sortByDesc('tahun_berlaku')
            ->values();
        $kurikulum = $daftarKurikulum->firstWhere('kurikulum_id', $kurikulumId) ?? $daftarKurikulum->first();

        // CPL dari kurikulum berbeda tidak pernah digabung (bagian 5.4), jadi semua isi dashboard
        // dihitung dari kelas kurikulum terpilih saja.
        $pesertaKurikulum = $daftarPeserta
            ->filter(fn (PesertaKelas $peserta) => $peserta->kelasKuliah->mataKuliah->kurikulum_id === $kurikulum?->kurikulum_id)
            ->values();
        $daftarCplKurikulum = $kurikulum === null
            ? collect()
            : Cpl::where('kurikulum_id', $kurikulum->kurikulum_id)->orderBy('kode_cpl')->get();

        $daftarCpl = $this->susunCpl($pesertaKurikulum, $daftarCplKurikulum);

        return [
            'pilihanKurikulum' => $daftarKurikulum->pluck('nama_kurikulum', 'kurikulum_id')->all(),
            'kurikulum' => $kurikulum,
            'ringkasan' => $this->ringkasan($pesertaKurikulum, $daftarCpl),
            'daftarCpl' => $daftarCpl,
            'perkembangan' => $this->perkembanganCpl($pesertaKurikulum, $daftarCplKurikulum),
            'daftarMataKuliah' => $this->daftarMataKuliah($pesertaKurikulum),
        ];
    }

    /**
     * Capaian CPL kumulatif (rumus 4) beserta status Lulus dan CPMK penyusunnya. MK yang diulang
     * memakai kelas terbaru yang sudah bernilai (bagian 5.4).
     *
     * @param  Collection<int, PesertaKelas>  $pesertaKurikulum
     * @param  Collection<int, Cpl>  $daftarCplKurikulum
     * @return Collection<int, array{kode: string, deskripsi: string, capaian: ?float, lulus: ?bool, penyusun: list<array>}>
     */
    private function susunCpl(Collection $pesertaKurikulum, Collection $daftarCplKurikulum): Collection
    {
        $kelasTerpilih = $this->capaian->pilihKelasPerMataKuliah($pesertaKurikulum);
        $capaianCpl = $this->capaian->hitungCapaianCpl($kelasTerpilih);
        $penyusun = $this->penyusunCpl($kelasTerpilih);

        return $daftarCplKurikulum->map(fn (Cpl $cpl) => [
            'kode' => $cpl->kode_cpl,
            'deskripsi' => $cpl->deskripsi_cpl,
            'capaian' => $capaianCpl[$cpl->cpl_id]['nilai'] ?? null,
            'lulus' => $this->capaian->lulus($capaianCpl[$cpl->cpl_id]['nilai'] ?? null),
            'penyusun' => $penyusun[$cpl->cpl_id] ?? [],
        ])->values();
    }

    /**
     * Pasangan CPMK–MK penyusun setiap CPL (k pada rumus 4) beserta capaian CPMK dan bobotnya
     * (BCPMK). Dengan daftar ini mahasiswa bisa melihat dari mana angka CPL-nya berasal.
     *
     * @param  Collection<int, PesertaKelas>  $kelasTerpilih  satu kelas per MK, hasil pilihKelasPerMataKuliah()
     * @return array<string, list<array>> cpl_id => daftar penyusun, urut kode CPMK lalu kode MK
     */
    private function penyusunCpl(Collection $kelasTerpilih): array
    {
        $daftarCapaian = $kelasTerpilih->map(fn (PesertaKelas $peserta) => [
            'kelas' => $peserta->kelasKuliah,
            'cpmk' => $this->capaian->hitungCapaianPeserta($peserta)['cpmk'],
        ]);
        // Kode CPMK diambil sekali untuk semua pasangan, bukan satu query per kelas.
        $daftarPemetaan = PemetaanCpmkMk::with('cpmk')
            ->whereIn('pemetaan_cpmk_mk_id', $daftarCapaian->flatMap(fn (array $hasil) => array_keys($hasil['cpmk'])))
            ->get()
            ->keyBy('pemetaan_cpmk_mk_id');

        $penyusun = [];

        foreach ($daftarCapaian as $hasil) {
            $kelas = $hasil['kelas'];

            foreach ($hasil['cpmk'] as $pemetaanId => $cpmk) {
                $penyusun[$cpmk['cpl_id']][] = [
                    'kode_cpmk' => $daftarPemetaan[$pemetaanId]->cpmk->kode_cpmk,
                    'kode_mata_kuliah' => $kelas->mataKuliah->kode_mata_kuliah,
                    'nama_mata_kuliah' => $kelas->mataKuliah->nama_mata_kuliah,
                    'periode' => $kelas->periodeAkademik->nama(),
                    'capaian' => $cpmk['nilai'],
                    'bobot' => $cpmk['bobot'],
                ];
            }
        }

        return array_map(
            fn (array $daftar) => collect($daftar)->sortBy([['kode_cpmk', 'asc'], ['kode_mata_kuliah', 'asc']])->values()->all(),
            $penyusun,
        );
    }

    /**
     * Angka untuk kartu ringkasan. Rata-rata CPL sama dengan kartu "Rata-rata CPL Kumulatif" di
     * halaman Capaian Pembelajaran. Jumlah CPL Lulus null jika AM belum ditetapkan (bagian 5.3).
     *
     * @param  Collection<int, PesertaKelas>  $pesertaKurikulum
     * @param  Collection<int, array>  $daftarCpl
     */
    private function ringkasan(Collection $pesertaKurikulum, Collection $daftarCpl): array
    {
        $nilaiCpl = $daftarCpl->pluck('capaian')->filter(fn (?float $capaian) => $capaian !== null);

        return [
            'rata_rata' => $nilaiCpl->isEmpty() ? null : $nilaiCpl->avg(),
            'jumlah_lulus' => $this->capaian->ambang()['am'] === null ? null : $daftarCpl->where('lulus', true)->count(),
            'jumlah_cpl' => $daftarCpl->count(),
            'jumlah_mata_kuliah' => $pesertaKurikulum->pluck('kelasKuliah.mata_kuliah_id')->unique()->count(),
            'jumlah_periode' => $pesertaKurikulum->pluck('kelasKuliah.periode_akademik_id')->unique()->count(),
        ];
    }

    /**
     * Capaian CPL kumulatif pada akhir setiap periode, karena CPL mahasiswa bisa dipantau setiap
     * tingkat sampai lulus (bagian 5.3). Untuk periode P dipakai semua kelas sampai periode P,
     * dengan aturan kelas terbaru yang sudah bernilai (bagian 5.4). Null jika kelasnya baru di
     * satu periode, karena satu titik belum menunjukkan perkembangan.
     *
     * @param  Collection<int, PesertaKelas>  $pesertaKurikulum
     * @param  Collection<int, Cpl>  $daftarCplKurikulum
     * @return array{periode: list<string>, nilai: list<list<?float>>}|null nilai[urutan CPL][urutan periode]
     */
    private function perkembanganCpl(Collection $pesertaKurikulum, Collection $daftarCplKurikulum): ?array
    {
        $daftarPeriode = $pesertaKurikulum->pluck('kelasKuliah.periodeAkademik')
            ->unique('periode_akademik_id')
            ->sortBy(fn (PeriodeAkademik $periode) => $periode->urutan())
            ->values();

        if ($daftarPeriode->count() < 2) {
            return null;
        }

        $capaianPerPeriode = $daftarPeriode->map(function (PeriodeAkademik $periode) use ($pesertaKurikulum) {
            $sampaiPeriodeIni = $pesertaKurikulum->filter(
                fn (PesertaKelas $peserta) => $peserta->kelasKuliah->periodeAkademik->urutan() <= $periode->urutan(),
            );

            return $this->capaian->hitungCapaianCpl($this->capaian->pilihKelasPerMataKuliah($sampaiPeriodeIni));
        });

        return [
            'periode' => $daftarPeriode->map(fn (PeriodeAkademik $periode) => $periode->nama())->all(),
            'nilai' => $daftarCplKurikulum
                ->map(fn (Cpl $cpl) => $capaianPerPeriode->map(fn (array $capaianCpl) => $capaianCpl[$cpl->cpl_id]['nilai'] ?? null)->all())
                ->all(),
        ];
    }

    /**
     * Kelas yang diikuti di kurikulum terpilih, terbaru dulu, beserta jumlah asesmen yang sudah
     * dinilai.
     *
     * @param  Collection<int, PesertaKelas>  $pesertaKurikulum
     * @return Collection<int, array{kelas: KelasKuliah, jumlah_asesmen: int, jumlah_dinilai: int}>
     */
    private function daftarMataKuliah(Collection $pesertaKurikulum): Collection
    {
        return $pesertaKurikulum
            ->sortBy([
                fn (PesertaKelas $a, PesertaKelas $b) => $b->kelasKuliah->periodeAkademik->urutan() <=> $a->kelasKuliah->periodeAkademik->urutan(),
                fn (PesertaKelas $a, PesertaKelas $b) => $a->kelasKuliah->mataKuliah->kode_mata_kuliah <=> $b->kelasKuliah->mataKuliah->kode_mata_kuliah,
            ])
            ->map(fn (PesertaKelas $peserta) => [
                'kelas' => $peserta->kelasKuliah,
                'jumlah_asesmen' => $peserta->kelasKuliah->rencanaAsesmen->count(),
                'jumlah_dinilai' => $peserta->nilaiAsesmen->count(),
            ])
            ->values();
    }
}
