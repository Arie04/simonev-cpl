<?php

namespace App\Services;

use App\Models\PesertaKelas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Perhitungan capaian Sub-CPMK, CPMK, dan CPL (bagian 5 spesifikasi, KF-11). Semua rumus
 * capaian hanya ada di class ini, lalu dipakai dashboard, laporan, dan halaman mahasiswa.
 *
 * Metode APTIKOM: capaian = Σ skor yang diperoleh / Σ skor maksimal × 100% (Tabel K). Di sini
 * metode itu ditulis sebagai rata-rata tertimbang bertingkat: asesmen → Sub-CPMK → CPMK → CPL
 * (rumus 1–4 bagian 5.2). Hasilnya sama persis dengan cara APTIKOM.
 *
 * Bobot yang dipakai selalu bobot_kontribusi rencana asesmen kelas, bukan bobot target di
 * kurikulum, supaya revisi kurikulum tidak mengubah hasil kelas lama (bagian 5.1, KD-08).
 */
class CapaianService
{
    /**
     * Relasi yang perlu dimuat bersama peserta kelas supaya perhitungan tidak memicu query
     * berulang, mis. PesertaKelas::with(CapaianService::RELASI_PESERTA).
     */
    public const RELASI_PESERTA = [
        'mahasiswa',
        'nilaiAsesmen',
        'kelasKuliah.periodeAkademik',
        'kelasKuliah.mataKuliah',
        'kelasKuliah.rencanaAsesmen.subCpmk.pemetaanCpmkMk.cpmk',
    ];

    /** @var array{am: ?float, ak: ?float}|null */
    private ?array $ambang = null;

    /**
     * Ambang batas dari config (bagian 5.3): AM = Ambang Batas Kelulusan Mahasiswa dan
     * AK = Ambang Batas Ketercapaian. Nilai hanya dianggap valid jika berupa angka 0–100; selain
     * itu dianggap belum ditetapkan dan peringatan dicatat di log (bagian 6).
     *
     * Ambang hanya diambil lewat method ini, jadi jika kelak ambang dipindah ke database, cukup
     * method ini yang diubah.
     *
     * @return array{am: ?float, ak: ?float}
     */
    public function ambang(): array
    {
        return $this->ambang ??= [
            'am' => $this->ambangValid('capaian.ambang_kelulusan_mahasiswa', 'AMBANG_KELULUSAN_MAHASISWA'),
            'ak' => $this->ambangValid('capaian.ambang_ketercapaian', 'AMBANG_KETERCAPAIAN'),
        ];
    }

    /**
     * Rumus 1: normalisasi nilai asesmen ke skala 0–100, Na = (Nm / Nmaks) × 100.
     */
    public function normalisasiNilai(float $nilai, float $nilaiMaksimum): float
    {
        return $nilai / $nilaiMaksimum * 100;
    }

    /**
     * Capaian satu mahasiswa di satu kelas (satu baris peserta_kelas).
     *
     * - Rumus 2: capaian Sub-CPMK, CSub = Σ(Na × bobot_kontribusi) / Σ bobot_kontribusi.
     * - Rumus 3: capaian CPMK per pasangan CPMK–MK, CCPMK = Σ(CSub × BSub) / Σ BSub.
     * - Rumus 5: nilai akhir MK = Σ(Na × bobot_asesmen) / 100, hanya jika semua asesmen bernilai.
     *
     * Asesmen yang belum punya nilai diabaikan, sehingga BSub dan BCPMK hanya berisi bobot yang
     * punya nilai (bagian 5.4, KD-47). Item tanpa nilai sama sekali tidak muncul di hasil.
     *
     * @return array{
     *     sub_cpmk: array<string, array{nilai: float, bobot: float, pemetaan_cpmk_mk_id: string}>,
     *     cpmk: array<string, array{nilai: float, bobot: float, cpl_id: string}>,
     *     nilai_akhir: ?float
     * }
     */
    public function hitungCapaianPeserta(PesertaKelas $peserta): array
    {
        $nilaiPerAsesmen = $peserta->nilaiAsesmen->pluck('nilai', 'rencana_asesmen_id');
        $daftarAsesmen = $peserta->kelasKuliah->rencanaAsesmen;

        // Rumus 2: jumlahkan Na × bobot_kontribusi dan bobot_kontribusi per Sub-CPMK.
        $jumlahSubCpmk = [];

        foreach ($daftarAsesmen as $asesmen) {
            if (! $nilaiPerAsesmen->has($asesmen->rencana_asesmen_id)) {
                continue;
            }

            $nilaiNormalisasi = $this->normalisasiNilai((float) $nilaiPerAsesmen[$asesmen->rencana_asesmen_id], (float) $asesmen->nilai_maksimum);

            foreach ($asesmen->subCpmk as $subCpmk) {
                $bobotKontribusi = (float) $subCpmk->pivot->bobot_kontribusi;

                $jumlahSubCpmk[$subCpmk->sub_cpmk_id] ??= [
                    'jumlah' => 0.0,
                    'bobot' => 0.0,
                    'pemetaan_cpmk_mk_id' => $subCpmk->pemetaan_cpmk_mk_id,
                    'cpl_id' => $subCpmk->pemetaanCpmkMk->cpmk->cpl_id,
                ];
                $jumlahSubCpmk[$subCpmk->sub_cpmk_id]['jumlah'] += $nilaiNormalisasi * $bobotKontribusi;
                $jumlahSubCpmk[$subCpmk->sub_cpmk_id]['bobot'] += $bobotKontribusi;
            }
        }

        $hasilSubCpmk = [];
        $jumlahCpmk = [];

        foreach ($jumlahSubCpmk as $subCpmkId => $jumlah) {
            if ($jumlah['bobot'] <= 0) {
                continue;
            }

            // CSub dan BSub (bobot Sub-CPMK yang benar-benar diases dan bernilai).
            $capaianSub = $jumlah['jumlah'] / $jumlah['bobot'];
            $hasilSubCpmk[$subCpmkId] = [
                'nilai' => $capaianSub,
                'bobot' => $jumlah['bobot'],
                'pemetaan_cpmk_mk_id' => $jumlah['pemetaan_cpmk_mk_id'],
            ];

            // Rumus 3: jumlahkan CSub × BSub dan BSub per pasangan CPMK–MK.
            $pemetaanId = $jumlah['pemetaan_cpmk_mk_id'];
            $jumlahCpmk[$pemetaanId] ??= ['jumlah' => 0.0, 'bobot' => 0.0, 'cpl_id' => $jumlah['cpl_id']];
            $jumlahCpmk[$pemetaanId]['jumlah'] += $capaianSub * $jumlah['bobot'];
            $jumlahCpmk[$pemetaanId]['bobot'] += $jumlah['bobot'];
        }

        $hasilCpmk = array_map(fn (array $jumlah) => [
            'nilai' => $jumlah['jumlah'] / $jumlah['bobot'],
            'bobot' => $jumlah['bobot'],
            'cpl_id' => $jumlah['cpl_id'],
        ], $jumlahCpmk);

        return [
            'sub_cpmk' => $hasilSubCpmk,
            'cpmk' => $hasilCpmk,
            'nilai_akhir' => $this->hitungNilaiAkhir($daftarAsesmen, $nilaiPerAsesmen),
        ];
    }

    /**
     * Rumus 4: capaian CPL seorang mahasiswa, CCPL = Σ(CCPMK × BCPMK) / Σ BCPMK, dari setiap
     * pasangan CPMK–MK di kelas-kelas yang diberikan. Jika satu CPMK dipakai di dua MK, CPMK itu
     * dihitung dua kali dengan bobotnya masing-masing (contoh CPMK063 APTIKOM).
     *
     * CPL dikelompokkan per cpl_id, jadi CPL dari kurikulum berbeda tidak pernah digabung walaupun
     * kodenya sama (bagian 5.4).
     *
     * @param  iterable<PesertaKelas>  $daftarPeserta  kelas-kelas satu mahasiswa, sebaiknya sudah
     *                                                 dipilih dengan pilihKelasPerMataKuliah()
     * @return array<string, array{nilai: float, bobot: float}> cpl_id => capaian
     */
    public function hitungCapaianCpl(iterable $daftarPeserta): array
    {
        $daftarHasil = [];

        foreach ($daftarPeserta as $peserta) {
            $daftarHasil[] = $this->hitungCapaianPeserta($peserta);
        }

        return $this->gabungkanCpl($daftarHasil);
    }

    /**
     * Memilih satu kelas per mahasiswa per MK (bagian 5.4, KD-24, KD-38): kelas terbaru tempat
     * mahasiswa itu sudah punya minimal satu nilai asesmen. Kelas tanpa nilai sama sekali tidak
     * dipakai, jadi mahasiswa yang baru mengulang tetap memakai kelas sebelumnya sampai kelas
     * barunya punya nilai.
     *
     * @param  Collection<int, PesertaKelas>  $daftarPeserta
     * @return Collection<int, PesertaKelas>
     */
    public function pilihKelasPerMataKuliah(Collection $daftarPeserta): Collection
    {
        return $daftarPeserta
            ->filter(fn (PesertaKelas $peserta) => $peserta->nilaiAsesmen->isNotEmpty())
            ->groupBy(fn (PesertaKelas $peserta) => $peserta->mahasiswa_id.'|'.$peserta->kelasKuliah->mata_kuliah_id)
            ->map(fn (Collection $kelompok) => $kelompok
                ->sortByDesc(fn (PesertaKelas $peserta) => $peserta->kelasKuliah->periodeAkademik->urutan())
                ->first())
            ->values();
    }

    /**
     * Capaian setiap mahasiswa untuk setiap item Sub-CPMK, CPMK (per pasangan CPMK–MK), dan CPL,
     * dari sekumpulan peserta kelas. Kelas per MK dipilih dulu dengan pilihKelasPerMataKuliah().
     * Mahasiswa yang belum punya nilai untuk sebuah item tidak tercantum di item itu.
     *
     * @param  Collection<int, PesertaKelas>  $daftarPeserta  dimuat dengan RELASI_PESERTA
     * @return array{sub_cpmk: array<string, array<string, float>>, cpmk: array<string, array<string, float>>, cpl: array<string, array<string, float>>}
     *                                                                                                                                                   jenis => id item => [mahasiswa_id => capaian]
     */
    public function capaianPerItem(Collection $daftarPeserta): array
    {
        $hasil = ['sub_cpmk' => [], 'cpmk' => [], 'cpl' => []];
        $hasilPerMahasiswa = [];

        foreach ($this->pilihKelasPerMataKuliah($daftarPeserta) as $peserta) {
            $capaian = $this->hitungCapaianPeserta($peserta);
            $hasilPerMahasiswa[$peserta->mahasiswa_id][] = $capaian;

            foreach ($capaian['sub_cpmk'] as $subCpmkId => $sub) {
                $hasil['sub_cpmk'][$subCpmkId][$peserta->mahasiswa_id] = $sub['nilai'];
            }

            foreach ($capaian['cpmk'] as $pemetaanId => $cpmk) {
                $hasil['cpmk'][$pemetaanId][$peserta->mahasiswa_id] = $cpmk['nilai'];
            }
        }

        foreach ($hasilPerMahasiswa as $mahasiswaId => $daftarHasil) {
            foreach ($this->gabungkanCpl($daftarHasil) as $cplId => $cpl) {
                $hasil['cpl'][$cplId][$mahasiswaId] = $cpl['nilai'];
            }
        }

        return $hasil;
    }

    /**
     * Status Lulus seorang mahasiswa pada suatu item: capaian ≥ AM (bagian 5.3). Keduanya
     * dibandingkan setelah dibulatkan 2 desimal, sama dengan angka yang tampil (KD-39).
     * Null jika capaian belum ada atau AM belum ditetapkan.
     */
    public function lulus(?float $capaian): ?bool
    {
        $ambangKelulusan = $this->ambang()['am'];

        if ($capaian === null || $ambangKelulusan === null) {
            return null;
        }

        return round($capaian, 2) >= round($ambangKelulusan, 2);
    }

    /**
     * Status Tercapai sebuah item: % mahasiswa Lulus ≥ AK (bagian 5.3), dibandingkan setelah
     * dibulatkan 2 desimal (KD-39). Null jika persentasenya belum ada atau AK belum ditetapkan.
     */
    public function tercapai(?float $persenLulus): ?bool
    {
        $ambangKetercapaian = $this->ambang()['ak'];

        if ($persenLulus === null || $ambangKetercapaian === null) {
            return null;
        }

        return round($persenLulus, 2) >= round($ambangKetercapaian, 2);
    }

    /**
     * Agregat satu item dari capaian banyak mahasiswa (bagian 5.3). Agregat utama adalah
     * persentase mahasiswa yang Lulus, bukan rata-rata nilai; rata-rata tetap dihitung sebagai
     * informasi pendukung. Capaian null (belum ada nilai) tidak ikut dihitung (bagian 5.4).
     *
     * @param  iterable<?float>  $daftarCapaian
     * @return array{jumlah_mahasiswa: int, rata_rata: ?float, jumlah_lulus: ?int, persen_lulus: ?float, tercapai: ?bool}
     */
    public function rekap(iterable $daftarCapaian): array
    {
        $daftarNilai = collect($daftarCapaian)->filter(fn ($capaian) => $capaian !== null)->values();
        $jumlahMahasiswa = $daftarNilai->count();

        if ($jumlahMahasiswa === 0) {
            return ['jumlah_mahasiswa' => 0, 'rata_rata' => null, 'jumlah_lulus' => null, 'persen_lulus' => null, 'tercapai' => null];
        }

        // Jika AM belum ditetapkan, jumlah dan persen Lulus tidak dihitung (bagian 5.3).
        $jumlahLulus = $this->ambang()['am'] === null
            ? null
            : $daftarNilai->filter(fn (float $capaian) => $this->lulus($capaian))->count();
        $persenLulus = $jumlahLulus === null ? null : $jumlahLulus / $jumlahMahasiswa * 100;

        return [
            'jumlah_mahasiswa' => $jumlahMahasiswa,
            'rata_rata' => $daftarNilai->avg(),
            'jumlah_lulus' => $jumlahLulus,
            'persen_lulus' => $persenLulus,
            'tercapai' => $this->tercapai($persenLulus),
        ];
    }

    /**
     * Rumus 4 dari hasil hitungCapaianPeserta() beberapa kelas satu mahasiswa.
     *
     * @param  list<array{cpmk: array<string, array{nilai: float, bobot: float, cpl_id: string}>}>  $daftarHasil
     * @return array<string, array{nilai: float, bobot: float}>
     */
    private function gabungkanCpl(array $daftarHasil): array
    {
        $jumlahCpl = [];

        foreach ($daftarHasil as $hasil) {
            foreach ($hasil['cpmk'] as $cpmk) {
                $jumlahCpl[$cpmk['cpl_id']] ??= ['jumlah' => 0.0, 'bobot' => 0.0];
                $jumlahCpl[$cpmk['cpl_id']]['jumlah'] += $cpmk['nilai'] * $cpmk['bobot'];
                $jumlahCpl[$cpmk['cpl_id']]['bobot'] += $cpmk['bobot'];
            }
        }

        return array_map(fn (array $jumlah) => [
            'nilai' => $jumlah['jumlah'] / $jumlah['bobot'],
            'bobot' => $jumlah['bobot'],
        ], $jumlahCpl);
    }

    /**
     * Rumus 5: nilai akhir MK, hanya untuk cek konsistensi (bukan fitur KHS). Dihitung hanya jika
     * semua asesmen kelas sudah punya nilai.
     */
    private function hitungNilaiAkhir(iterable $daftarAsesmen, Collection $nilaiPerAsesmen): ?float
    {
        $nilaiAkhir = 0.0;
        $adaAsesmen = false;

        foreach ($daftarAsesmen as $asesmen) {
            if (! $nilaiPerAsesmen->has($asesmen->rencana_asesmen_id)) {
                return null;
            }

            $adaAsesmen = true;
            $nilaiNormalisasi = $this->normalisasiNilai((float) $nilaiPerAsesmen[$asesmen->rencana_asesmen_id], (float) $asesmen->nilai_maksimum);
            $nilaiAkhir += $nilaiNormalisasi * (float) $asesmen->bobot_asesmen / 100;
        }

        return $adaAsesmen ? $nilaiAkhir : null;
    }

    /**
     * Membaca satu ambang dari config. Kosong berarti belum ditetapkan; selain angka 0–100
     * dianggap belum ditetapkan dan dicatat di log (bagian 6 spesifikasi).
     */
    private function ambangValid(string $kunciConfig, string $namaEnv): ?float
    {
        $nilai = config($kunciConfig);

        if ($nilai === null || $nilai === '') {
            return null;
        }

        if (! is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
            Log::warning("{$namaEnv} di .env tidak valid (\"{$nilai}\"), jadi dianggap belum ditetapkan. Isi dengan angka 0–100.");

            return null;
        }

        return (float) $nilai;
    }
}
