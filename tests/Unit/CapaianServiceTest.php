<?php

namespace Tests\Unit;

use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeAkademik;
use App\Models\PesertaKelas;
use App\Services\CapaianService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Support\MembangunDataCapaian;
use Tests\TestCase;

/**
 * Unit test CapaianService dengan contoh uji bagian 5.5 spesifikasi (Uji 1–6), ditambah kasus
 * nilai belum lengkap, mahasiswa mengulang, dan pembulatan status.
 */
class CapaianServiceTest extends TestCase
{
    use MembangunDataCapaian;
    use RefreshDatabase;

    private CapaianService $capaian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->capaian = new CapaianService;
        config(['capaian.ambang_kelulusan_mahasiswa' => 50.01, 'capaian.ambang_ketercapaian' => 85]);
    }

    private function assertCapaian(float $diharapkan, ?float $hasil, string $pesan = ''): void
    {
        $this->assertNotNull($hasil, $pesan);
        $this->assertEqualsWithDelta($diharapkan, $hasil, 0.005, $pesan);
    }

    /**
     * Data Uji 1: MK Matematika Diskrit kelas A, CPMK-X (S1 25, S2 35) dan CPMK-Y (S3 40).
     *
     * @param  array<string, int>  $nilai  nama asesmen => nilai mahasiswa
     * @return array{0: MataKuliah, 1: PesertaKelas, 2: Kurikulum}
     */
    private function dataUji1(array $nilai = ['Kuis 1' => 40, 'Tugas 1' => 85, 'UTS' => 70, 'UAS' => 90]): array
    {
        $kurikulum = Kurikulum::factory()->create();
        $mataKuliah = $this->buatMataKuliah($kurikulum, 'MD', [
            'CPMK-X' => ['cpl' => 'CPL-01', 'bobot' => 60, 'sub' => ['S1' => 25, 'S2' => 35]],
            'CPMK-Y' => ['cpl' => 'CPL-02', 'bobot' => 40, 'sub' => ['S3' => 40]],
        ]);
        $kelas = $this->buatKelas($mataKuliah, [
            'Kuis 1' => ['bobot' => 10, 'maks' => 50, 'kontribusi' => ['S1' => 10]],
            'Tugas 1' => ['bobot' => 20, 'maks' => 100, 'kontribusi' => ['S2' => 20]],
            'UTS' => ['bobot' => 30, 'maks' => 100, 'kontribusi' => ['S1' => 15, 'S2' => 15]],
            'UAS' => ['bobot' => 40, 'maks' => 100, 'kontribusi' => ['S3' => 40]],
        ]);
        $this->beriNilai($kelas, Mahasiswa::factory()->create(), $nilai);

        return [$mataKuliah, $this->muatPeserta()->first(), $kurikulum];
    }

    public function test_uji_1_asesmen_ke_sub_cpmk_ke_cpmk_dan_nilai_akhir(): void
    {
        [$mataKuliah, $peserta] = $this->dataUji1();

        $hasil = $this->capaian->hitungCapaianPeserta($peserta);
        $sub = fn (string $kode) => $hasil['sub_cpmk'][$this->subCpmk($mataKuliah, $kode)->sub_cpmk_id];
        $cpmk = fn (string $kode) => $hasil['cpmk'][$this->pemetaan($mataKuliah, $kode)->pemetaan_cpmk_mk_id];

        $this->assertCapaian(74.00, $sub('S1')['nilai']);
        $this->assertCapaian(78.57, $sub('S2')['nilai']);
        $this->assertCapaian(90.00, $sub('S3')['nilai']);
        $this->assertCapaian(76.67, $cpmk('CPMK-X')['nilai']);
        $this->assertCapaian(90.00, $cpmk('CPMK-Y')['nilai']);
        $this->assertCapaian(82.00, $hasil['nilai_akhir']);

        // Kelas sesuai target: BSub = 25, 35, 40 dan BCPMK = 60, 40.
        $this->assertSame([25.0, 35.0, 40.0], [$sub('S1')['bobot'], $sub('S2')['bobot'], $sub('S3')['bobot']]);
        $this->assertSame([60.0, 40.0], [$cpmk('CPMK-X')['bobot'], $cpmk('CPMK-Y')['bobot']]);

        // Cek konsistensi rumus 5: Σ(CCPMK × BCPMK) / 100 = nilai akhir MK (Tabel G APTIKOM).
        $this->assertCapaian(
            $hasil['nilai_akhir'],
            ($cpmk('CPMK-X')['nilai'] * $cpmk('CPMK-X')['bobot'] + $cpmk('CPMK-Y')['nilai'] * $cpmk('CPMK-Y')['bobot']) / 100,
        );
    }

    public function test_uji_2_capaian_cpl_sama_dengan_tabel_k_aptikom(): void
    {
        $kurikulum = Kurikulum::factory()->create();

        // Setiap CPMK diwakili satu Sub-CPMK dan satu asesmen dengan skor maks = bobotnya (Tabel 19).
        $struktur = fn (array $daftarCpmk) => collect($daftarCpmk)->mapWithKeys(fn (array $data, string $kode) => [
            $kode => ['cpl' => $data[0], 'bobot' => $data[1], 'sub' => ['S-'.$kode => $data[1]]],
        ])->all();
        $asesmen = fn (array $daftarCpmk) => collect($daftarCpmk)->mapWithKeys(fn (array $data, string $kode) => [
            'Asesmen '.$kode => ['bobot' => $data[1], 'maks' => $data[1], 'kontribusi' => ['S-'.$kode => $data[1]]],
        ])->all();

        $cpmkMk04 = ['CPMK061' => ['CPL06', 15], 'CPMK062' => ['CPL06', 15], 'CPMK063' => ['CPL06', 20], 'CPMK081' => ['CPL08', 15], 'CPMK082' => ['CPL08', 15], 'CPMK083' => ['CPL08', 20]];
        $cpmkMk34 = ['CPMK063' => ['CPL06', 30], 'CPMK071' => ['CPL07', 40], 'CPMK072' => ['CPL07', 30]];

        $mk04 = $this->buatMataKuliah($kurikulum, 'MK04', $struktur($cpmkMk04));
        $mk34 = $this->buatMataKuliah($kurikulum, 'MK34', $struktur($cpmkMk34));
        $periode = PeriodeAkademik::factory()->create(['tahun_akademik' => '2026/2027', 'semester_periode' => 'Ganjil']);
        $kelasMk04 = $this->buatKelas($mk04, $asesmen($cpmkMk04), $periode);
        $kelasMk34 = $this->buatKelas($mk34, $asesmen($cpmkMk34), $periode);

        // Skor mahasiswa: MK04 (061, 062, 063, 081, 082, 083), MK34 (063, 071, 072) => CPL06, CPL08, CPL07.
        $tabelK = [
            'Mhs 1' => [[10, 10, 15, 10, 10, 15], [25, 35, 25], [75.00, 70.00, 85.71]],
            'Mhs 2' => [[15, 15, 15, 10, 15, 15], [20, 35, 25], [81.25, 80.00, 85.71]],
            'Mhs 3' => [[15, 14, 17, 13, 12, 16], [25, 36, 28], [88.75, 82.00, 91.43]],
            'Mhs 4' => [[15, 13, 18, 14, 13, 18], [28, 35, 28], [92.50, 90.00, 90.00]],
        ];

        foreach ($tabelK as $nama => [$skorMk04, $skorMk34, $diharapkan]) {
            $mahasiswa = Mahasiswa::factory()->create(['nama' => $nama]);
            $this->beriNilai($kelasMk04, $mahasiswa, array_combine(array_map(fn ($kode) => 'Asesmen '.$kode, array_keys($cpmkMk04)), $skorMk04));
            $this->beriNilai($kelasMk34, $mahasiswa, array_combine(array_map(fn ($kode) => 'Asesmen '.$kode, array_keys($cpmkMk34)), $skorMk34));

            $cpl = $this->capaian->hitungCapaianCpl($this->muatPeserta(fn ($query) => $query->where('mahasiswa_id', $mahasiswa->mahasiswa_id)));

            $this->assertCapaian($diharapkan[0], $cpl[$this->cpl($kurikulum, 'CPL06')->cpl_id]['nilai'], "{$nama} CPL06");
            $this->assertCapaian($diharapkan[1], $cpl[$this->cpl($kurikulum, 'CPL08')->cpl_id]['nilai'], "{$nama} CPL08");
            $this->assertCapaian($diharapkan[2], $cpl[$this->cpl($kurikulum, 'CPL07')->cpl_id]['nilai'], "{$nama} CPL07");
        }

        // CPMK063 dipakai di dua MK, jadi dihitung dua kali dengan bobotnya masing-masing (20 + 30).
        $mahasiswaPertama = Mahasiswa::where('nama', 'Mhs 1')->first();
        $cpl06 = $this->capaian->hitungCapaianCpl($this->muatPeserta(fn ($query) => $query->where('mahasiswa_id', $mahasiswaPertama->mahasiswa_id)))[$this->cpl($kurikulum, 'CPL06')->cpl_id];
        $this->assertSame(80.0, $cpl06['bobot']);
    }

    /**
     * Data Uji 3: satu kelas berisi 50 mahasiswa untuk satu CPMK. 30 mahasiswa di atas 50,01,
     * 5 mahasiswa tepat 50,01, dan 15 mahasiswa di bawah 50,01.
     *
     * @return array<string, float>
     */
    private function dataUji3(): array
    {
        $kurikulum = Kurikulum::factory()->create();
        $mataKuliah = $this->buatMataKuliah($kurikulum, 'MK01', ['CPMK011' => ['cpl' => 'CPL01', 'bobot' => 100, 'sub' => ['S1' => 100]]]);
        $kelas = $this->buatKelas($mataKuliah, ['UAS' => ['bobot' => 100, 'maks' => 100, 'kontribusi' => ['S1' => 100]]]);

        foreach ([...array_fill(0, 30, 60), ...array_fill(0, 5, 50.01), ...array_fill(0, 15, 40)] as $nilai) {
            $this->beriNilai($kelas, Mahasiswa::factory()->create(), ['UAS' => $nilai]);
        }

        return $this->capaian->capaianPerItem($this->muatPeserta())['cpmk'][$this->pemetaan($mataKuliah, 'CPMK011')->pemetaan_cpmk_mk_id];
    }

    public function test_uji_3_agregat_adalah_persen_mahasiswa_lulus_bukan_rata_rata(): void
    {
        $rekap = $this->capaian->rekap($this->dataUji3());

        $this->assertSame(50, $rekap['jumlah_mahasiswa']);
        $this->assertSame(35, $rekap['jumlah_lulus']);
        $this->assertCapaian(70.00, $rekap['persen_lulus']);
        $this->assertFalse($rekap['tercapai'], '70% < 85%, jadi Tidak Tercapai.');
        $this->assertCapaian(53.00, $rekap['rata_rata']);
    }

    public function test_uji_4_ambang_belum_diisi_nilai_tetap_terhitung_tanpa_status(): void
    {
        $daftarCapaian = $this->dataUji3();
        config(['capaian.ambang_kelulusan_mahasiswa' => null, 'capaian.ambang_ketercapaian' => null]);
        $capaian = new CapaianService;

        $rekap = $capaian->rekap($daftarCapaian);

        $this->assertSame(['am' => null, 'ak' => null], $capaian->ambang());
        $this->assertSame(50, $rekap['jumlah_mahasiswa']);
        $this->assertCapaian(53.00, $rekap['rata_rata']);
        $this->assertNull($rekap['jumlah_lulus']);
        $this->assertNull($rekap['persen_lulus']);
        $this->assertNull($rekap['tercapai']);
        $this->assertNull($capaian->lulus(60));
    }

    public function test_uji_5_revisi_bobot_kurikulum_tidak_mengubah_hasil_lama(): void
    {
        [$mataKuliah, $peserta] = $this->dataUji1();

        $this->subCpmk($mataKuliah, 'S1')->update(['bobot_sub_cpmk' => 30]);
        $this->subCpmk($mataKuliah, 'S2')->update(['bobot_sub_cpmk' => 30]);
        $hasil = $this->capaian->hitungCapaianPeserta($this->muatPeserta()->first());

        $this->assertCapaian(74.00, $hasil['sub_cpmk'][$this->subCpmk($mataKuliah, 'S1')->sub_cpmk_id]['nilai']);
        $this->assertCapaian(78.57, $hasil['sub_cpmk'][$this->subCpmk($mataKuliah, 'S2')->sub_cpmk_id]['nilai']);
        $this->assertCapaian(76.67, $hasil['cpmk'][$this->pemetaan($mataKuliah, 'CPMK-X')->pemetaan_cpmk_mk_id]['nilai']);
        $this->assertCapaian(82.00, $hasil['nilai_akhir']);
    }

    public function test_uji_6_cpl_kurikulum_lain_dengan_kode_sama_tidak_ikut_terhitung(): void
    {
        $kurikulum2021 = Kurikulum::factory()->create(['tahun_berlaku' => 2021]);
        $kurikulum2026 = Kurikulum::factory()->create(['tahun_berlaku' => 2026]);
        $struktur = ['CPMK011' => ['cpl' => 'CPL01', 'bobot' => 100, 'sub' => ['S1' => 100]]];
        $this->buatMataKuliah($kurikulum2021, 'INF202', $struktur);
        $mk2026 = $this->buatMataKuliah($kurikulum2026, 'INF202', $struktur);
        $kelas = $this->buatKelas($mk2026, ['UAS' => ['bobot' => 100, 'maks' => 100, 'kontribusi' => ['S1' => 100]]]);
        $this->beriNilai($kelas, Mahasiswa::factory()->create(), ['UAS' => 80]);

        $cpl = $this->capaian->hitungCapaianCpl($this->muatPeserta());

        $this->assertSame([$this->cpl($kurikulum2026, 'CPL01')->cpl_id], array_keys($cpl));
        $this->assertArrayNotHasKey($this->cpl($kurikulum2021, 'CPL01')->cpl_id, $cpl);
    }

    public function test_cpl_mahasiswa_yang_mengikuti_dua_kurikulum_dipisah_per_kurikulum(): void
    {
        $kurikulum2021 = Kurikulum::factory()->create(['tahun_berlaku' => 2021]);
        $kurikulum2026 = Kurikulum::factory()->create(['tahun_berlaku' => 2026]);
        $struktur = ['CPMK011' => ['cpl' => 'CPL01', 'bobot' => 100, 'sub' => ['S1' => 100]]];
        $asesmen = ['UAS' => ['bobot' => 100, 'maks' => 100, 'kontribusi' => ['S1' => 100]]];
        $periode = PeriodeAkademik::factory()->create(['tahun_akademik' => '2026/2027', 'semester_periode' => 'Ganjil']);
        $kelas2021 = $this->buatKelas($this->buatMataKuliah($kurikulum2021, 'INF301', $struktur), $asesmen, $periode);
        $kelas2026 = $this->buatKelas($this->buatMataKuliah($kurikulum2026, 'INF101', $struktur), $asesmen, $periode);
        $mahasiswa = Mahasiswa::factory()->create();
        $this->beriNilai($kelas2021, $mahasiswa, ['UAS' => 60]);
        $this->beriNilai($kelas2026, $mahasiswa, ['UAS' => 90]);

        $cpl = $this->capaian->hitungCapaianCpl($this->muatPeserta());

        $this->assertCount(2, $cpl);
        $this->assertCapaian(60.00, $cpl[$this->cpl($kurikulum2021, 'CPL01')->cpl_id]['nilai']);
        $this->assertCapaian(90.00, $cpl[$this->cpl($kurikulum2026, 'CPL01')->cpl_id]['nilai']);
    }

    public function test_nilai_sebagian_kosong_diabaikan_dan_bobotnya_dinormalisasi_ulang(): void
    {
        // Seperti Uji 1, tetapi UTS dan UAS belum dinilai.
        [$mataKuliah, $peserta] = $this->dataUji1(['Kuis 1' => 40, 'Tugas 1' => 85]);

        $hasil = $this->capaian->hitungCapaianPeserta($peserta);
        $cpmkX = $hasil['cpmk'][$this->pemetaan($mataKuliah, 'CPMK-X')->pemetaan_cpmk_mk_id];

        $this->assertCapaian(80.00, $hasil['sub_cpmk'][$this->subCpmk($mataKuliah, 'S1')->sub_cpmk_id]['nilai']);
        $this->assertCapaian(85.00, $hasil['sub_cpmk'][$this->subCpmk($mataKuliah, 'S2')->sub_cpmk_id]['nilai']);
        // CPMK-X = (80 × 10 + 85 × 20) / 30; penyebut hanya bobot yang punya nilai (KD-47).
        $this->assertCapaian(83.33, $cpmkX['nilai']);
        $this->assertSame(30.0, $cpmkX['bobot']);
        // S3 (hanya diukur UAS) belum ada nilai, jadi CPMK-Y tidak muncul, bukan 0.
        $this->assertArrayNotHasKey($this->subCpmk($mataKuliah, 'S3')->sub_cpmk_id, $hasil['sub_cpmk']);
        $this->assertArrayNotHasKey($this->pemetaan($mataKuliah, 'CPMK-Y')->pemetaan_cpmk_mk_id, $hasil['cpmk']);
        $this->assertNull($hasil['nilai_akhir'], 'Nilai akhir MK hanya dihitung jika semua asesmen sudah bernilai.');
    }

    public function test_semua_nilai_kosong_menghasilkan_belum_ada_nilai_dan_tidak_ikut_agregat(): void
    {
        [, $peserta] = $this->dataUji1([]);

        $hasil = $this->capaian->hitungCapaianPeserta($peserta);

        $this->assertSame([], $hasil['sub_cpmk']);
        $this->assertSame([], $hasil['cpmk']);
        $this->assertNull($hasil['nilai_akhir']);
        $this->assertSame([], $this->capaian->hitungCapaianCpl([$peserta]));
        $this->assertSame(['sub_cpmk' => [], 'cpmk' => [], 'cpl' => []], $this->capaian->capaianPerItem($this->muatPeserta()));
        $this->assertSame(0, $this->capaian->rekap([null, null])['jumlah_mahasiswa']);
    }

    public function test_mahasiswa_mengulang_memakai_kelas_terbaru_yang_sudah_bernilai(): void
    {
        $kurikulum = Kurikulum::factory()->create();
        $mataKuliah = $this->buatMataKuliah($kurikulum, 'INF201', ['CPMK021' => ['cpl' => 'CPL02', 'bobot' => 100, 'sub' => ['S1' => 50, 'S2' => 50]]]);
        $asesmen = [
            'UTS' => ['bobot' => 50, 'maks' => 100, 'kontribusi' => ['S1' => 50]],
            'UAS' => ['bobot' => 50, 'maks' => 100, 'kontribusi' => ['S2' => 50]],
        ];
        $kelasLama = $this->buatKelas($mataKuliah, $asesmen, PeriodeAkademik::factory()->create(['tahun_akademik' => '2025/2026', 'semester_periode' => 'Genap']));
        $kelasBaru = $this->buatKelas($mataKuliah, $asesmen, PeriodeAkademik::factory()->create(['tahun_akademik' => '2026/2027', 'semester_periode' => 'Ganjil']));
        $mahasiswa = Mahasiswa::factory()->create();
        $cplId = $this->cpl($kurikulum, 'CPL02')->cpl_id;

        $this->beriNilai($kelasLama, $mahasiswa, ['UTS' => 40, 'UAS' => 30]);
        $this->beriNilai($kelasBaru, $mahasiswa, []);

        // Kelas baru belum punya nilai: capaian tetap dari kelas lama, tidak hilang di awal semester.
        $this->assertCapaian(35.00, $this->capaian->capaianPerItem($this->muatPeserta())['cpl'][$cplId][$mahasiswa->mahasiswa_id]);

        // Begitu kelas baru punya satu nilai, kelas baru yang dipakai (nilai parsial).
        $this->beriNilai($kelasBaru, $mahasiswa, ['UTS' => 80]);
        $this->assertCapaian(80.00, $this->capaian->capaianPerItem($this->muatPeserta())['cpl'][$cplId][$mahasiswa->mahasiswa_id]);

        // Mahasiswa hanya dihitung sekali per MK di tab CPMK (KD-38).
        $this->assertCount(1, $this->capaian->capaianPerItem($this->muatPeserta())['cpmk'][$this->pemetaan($mataKuliah, 'CPMK021')->pemetaan_cpmk_mk_id]);
    }

    public function test_filter_periode_hanya_memakai_kelas_pada_periode_itu(): void
    {
        $kurikulum = Kurikulum::factory()->create();
        $mataKuliah = $this->buatMataKuliah($kurikulum, 'INF201', ['CPMK021' => ['cpl' => 'CPL02', 'bobot' => 100, 'sub' => ['S1' => 100]]]);
        $asesmen = ['UAS' => ['bobot' => 100, 'maks' => 100, 'kontribusi' => ['S1' => 100]]];
        $periodeLama = PeriodeAkademik::factory()->create(['tahun_akademik' => '2025/2026', 'semester_periode' => 'Genap']);
        $kelasLama = $this->buatKelas($mataKuliah, $asesmen, $periodeLama);
        $kelasBaru = $this->buatKelas($mataKuliah, $asesmen, PeriodeAkademik::factory()->create(['tahun_akademik' => '2026/2027', 'semester_periode' => 'Ganjil']));
        $mahasiswa = Mahasiswa::factory()->create();
        $this->beriNilai($kelasLama, $mahasiswa, ['UAS' => 40]);
        $this->beriNilai($kelasBaru, $mahasiswa, ['UAS' => 80]);

        // Filter periode lama (KD-23): hanya kelas pada periode itu yang dihitung.
        $pesertaPeriodeLama = $this->muatPeserta(fn ($query) => $query->whereHas('kelasKuliah', fn ($kelas) => $kelas->where('periode_akademik_id', $periodeLama->periode_akademik_id)));

        $this->assertCapaian(40.00, $this->capaian->hitungCapaianCpl($this->capaian->pilihKelasPerMataKuliah($pesertaPeriodeLama))[$this->cpl($kurikulum, 'CPL02')->cpl_id]['nilai']);
    }

    public function test_status_dibandingkan_dengan_nilai_yang_dibulatkan_dua_desimal(): void
    {
        // AM = 50,01 dan AK = 85 (KD-39).
        $this->assertTrue($this->capaian->lulus(50.006), '50,006 tampil 50,01, jadi Lulus.');
        $this->assertTrue($this->capaian->lulus(50.01));
        $this->assertFalse($this->capaian->lulus(50.004), '50,004 tampil 50,00, jadi Belum Lulus.');
        $this->assertNull($this->capaian->lulus(null));

        $this->assertTrue($this->capaian->tercapai(84.996));
        $this->assertFalse($this->capaian->tercapai(84.994));
    }

    public function test_ambang_yang_tidak_valid_dianggap_belum_ditetapkan_dan_dicatat_di_log(): void
    {
        Log::spy();
        config(['capaian.ambang_kelulusan_mahasiswa' => 'lima puluh', 'capaian.ambang_ketercapaian' => 150]);

        $this->assertSame(['am' => null, 'ak' => null], (new CapaianService)->ambang());

        Log::shouldHaveReceived('warning')->twice();
    }

    public function test_ambang_dibaca_dari_config_dengan_titik_desimal(): void
    {
        config(['capaian.ambang_kelulusan_mahasiswa' => '50.01', 'capaian.ambang_ketercapaian' => '85']);

        $this->assertSame(['am' => 50.01, 'ak' => 85.0], (new CapaianService)->ambang());
    }
}
