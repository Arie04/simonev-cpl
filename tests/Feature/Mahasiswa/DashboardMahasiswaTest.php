<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeAkademik;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\MembangunDataCapaian;
use Tests\TestCase;

/**
 * Dashboard Mahasiswa (KD-74): ringkasan capaian CPL kumulatif milik mahasiswa yang login, dengan
 * grafik CPL, rincian CPMK penyusun setiap CPL, dan perkembangan CPL per periode.
 */
class DashboardMahasiswaTest extends TestCase
{
    use MembangunDataCapaian;
    use RefreshDatabase;

    private Kurikulum $kurikulum;

    private MataKuliah $mataKuliahGenap;

    private MataKuliah $mataKuliahGanjil;

    private PeriodeAkademik $periodeGenap;

    private PeriodeAkademik $periodeGanjil;

    private KelasKuliah $kelasGenap;

    private Mahasiswa $mahasiswa;

    private Mahasiswa $temanSekelas;

    /**
     * Data uji, dihitung dengan rumus 1–4 bagian 5.2:
     * - 2025/2026 Genap, INF101: CPMK011 (CPL01, bobot 50) dan CPMK021 (CPL02, bobot 50).
     *   Aisyah: UTS 80 → CPMK011 = 80; UAS 40 → CPMK021 = 40.
     * - 2026/2027 Ganjil, INF103: CPMK012 (CPL01, bobot 100). Aisyah: Proyek 60 → CPMK012 = 60.
     * - CPL01 kumulatif = (80 × 50 + 60 × 100) / 150 = 66,67; CPL02 = 40.
     * - CPL03 milik INF102 yang tidak diikuti Aisyah, jadi belum ada nilai.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['capaian.ambang_kelulusan_mahasiswa' => 50.01, 'capaian.ambang_ketercapaian' => 85]);

        $this->kurikulum = Kurikulum::factory()->create(['tahun_berlaku' => 2026, 'nama_kurikulum' => 'Kurikulum OBE 2026']);
        $this->periodeGenap = PeriodeAkademik::factory()->create(['tahun_akademik' => '2025/2026', 'semester_periode' => 'Genap']);
        $this->periodeGanjil = PeriodeAkademik::factory()->create(['tahun_akademik' => '2026/2027', 'semester_periode' => 'Ganjil']);

        $this->mataKuliahGenap = $this->buatMataKuliah($this->kurikulum, 'INF101', [
            'CPMK011' => ['cpl' => 'CPL01', 'bobot' => 50, 'sub' => ['Sub-CPMK0111' => 50]],
            'CPMK021' => ['cpl' => 'CPL02', 'bobot' => 50, 'sub' => ['Sub-CPMK0211' => 50]],
        ]);
        $this->mataKuliahGanjil = $this->buatMataKuliah($this->kurikulum, 'INF103', [
            'CPMK012' => ['cpl' => 'CPL01', 'bobot' => 100, 'sub' => ['Sub-CPMK0121' => 100]],
        ]);
        $this->buatMataKuliah($this->kurikulum, 'INF102', [
            'CPMK031' => ['cpl' => 'CPL03', 'bobot' => 100, 'sub' => ['Sub-CPMK0311' => 100]],
        ]);

        $this->kelasGenap = $this->buatKelas($this->mataKuliahGenap, [
            'UTS' => ['bobot' => 50, 'maks' => 100, 'kontribusi' => ['Sub-CPMK0111' => 50]],
            'UAS' => ['bobot' => 50, 'maks' => 100, 'kontribusi' => ['Sub-CPMK0211' => 50]],
        ], $this->periodeGenap);
        $kelasGanjil = $this->buatKelas($this->mataKuliahGanjil, [
            'Proyek' => ['bobot' => 100, 'maks' => 100, 'kontribusi' => ['Sub-CPMK0121' => 100]],
        ], $this->periodeGanjil);

        $this->mahasiswa = Mahasiswa::factory()->create(['nama' => 'Aisyah Putri', 'nim' => '9992300002']);
        $this->temanSekelas = Mahasiswa::factory()->create(['nama' => 'Bagas Pratama', 'nim' => '9992300003']);
        $this->beriNilai($this->kelasGenap, $this->mahasiswa, ['UTS' => 80, 'UAS' => 40]);
        $this->beriNilai($kelasGanjil, $this->mahasiswa, ['Proyek' => 60]);
        $this->beriNilai($this->kelasGenap, $this->temanSekelas, ['UTS' => 10, 'UAS' => 99]);
    }

    private function bukaDashboard(array $parameter = [], ?Mahasiswa $mahasiswa = null): TestResponse
    {
        $this->masukSebagai(Role::MAHASISWA, ($mahasiswa ?? $this->mahasiswa)->user);

        return $this->get(route('mahasiswa.dashboard', $parameter));
    }

    /**
     * Ringkasan CPL dari data view: kode => [capaian dibulatkan 2 desimal, status Lulus].
     */
    private function ringkasCpl(TestResponse $response): array
    {
        return $response->viewData('daftarCpl')
            ->mapWithKeys(fn (array $cpl) => [$cpl['kode'] => [$cpl['capaian'] === null ? null : round($cpl['capaian'], 2), $cpl['lulus']]])
            ->all();
    }

    public function test_beranda_mahasiswa_diarahkan_ke_dashboard(): void
    {
        $this->masukSebagai(Role::MAHASISWA, $this->mahasiswa->user);

        $this->get(route('beranda'))->assertRedirect(route('mahasiswa.dashboard'));
    }

    public function test_menu_mahasiswa_berisi_dashboard_dan_capaian_pembelajaran(): void
    {
        $this->bukaDashboard()
            ->assertOk()
            ->assertSee('href="'.route('mahasiswa.dashboard').'"', false)
            ->assertSee('href="'.route('mahasiswa.capaian').'"', false)
            // Urutan di sidebar: Dashboard lalu Capaian Pembelajaran.
            ->assertSeeInOrder(['aria-label="Menu utama"', 'href="'.route('mahasiswa.dashboard').'"', 'href="'.route('mahasiswa.capaian').'"'], false);
    }

    public function test_ringkasan_capaian_cpl_kumulatif_milik_sendiri(): void
    {
        $response = $this->bukaDashboard()
            ->assertOk()
            ->assertSee('Selamat datang, Aisyah Putri')
            ->assertSeeInOrder(['Rata-rata CPL', '53,33', 'CPL Lulus', '1 dari 3', 'Mata Kuliah Diikuti', '>2</p>', '2 periode'], false)
            ->assertDontSee('Bagas Pratama')
            ->assertDontSee('9992300003');

        $this->assertSame([
            'CPL01' => [66.67, true],
            'CPL02' => [40.0, false],
            'CPL03' => [null, null],
        ], $this->ringkasCpl($response));
        $this->assertSame(2, $response->viewData('ringkasan')['jumlah_mata_kuliah']);
        $this->assertSame(2, $response->viewData('ringkasan')['jumlah_periode']);
    }

    public function test_data_grafik_berisi_status_warna_dan_cpl_terpilih_awal(): void
    {
        // Data grafik ada di atribut x-data (Js::from), dengan tanda kutip ditulis \u0022.
        $this->bukaDashboard()
            ->assertSee('\u0022status\u0022:[\u0022lulus\u0022,\u0022belum\u0022,\u0022kosong\u0022]', false)
            ->assertSee('\u0022terpilih\u0022:0', false);

        // CPL terpilih awal adalah CPL pertama yang sudah punya nilai: CPL02 (urutan 1), karena
        // mahasiswa ini hanya punya nilai UAS (CPMK021).
        $this->beriNilai($this->kelasGenap, $mahasiswaBaru = Mahasiswa::factory()->create(), ['UAS' => 70]);
        $this->bukaDashboard(mahasiswa: $mahasiswaBaru)
            ->assertSee('\u0022status\u0022:[\u0022kosong\u0022,\u0022lulus\u0022,\u0022kosong\u0022]', false)
            ->assertSee('\u0022terpilih\u0022:1', false);
    }

    public function test_rincian_cpl_menampilkan_cpmk_penyusun_beserta_capaian_dan_bobotnya(): void
    {
        $response = $this->bukaDashboard()
            ->assertSeeInOrder(['Rincian CPL01', 'Deskripsi CPL01', '66,67', 'CPMK011 · INF101', '80,00', '50', 'CPMK012 · INF103', '60,00', '100'])
            ->assertSeeInOrder(['Rincian CPL03', 'Belum ada nilai']);

        // Capaian CPL sama dengan rata-rata capaian CPMK penyusunnya yang ditimbang bobot (rumus 4).
        $cpl01 = $response->viewData('daftarCpl')->firstWhere('kode', 'CPL01');
        $penyusun = collect($cpl01['penyusun']);
        $this->assertSame(['CPMK011', 'CPMK012'], $penyusun->pluck('kode_cpmk')->all());
        $this->assertEqualsWithDelta(
            $cpl01['capaian'],
            $penyusun->sum(fn (array $baris) => $baris['capaian'] * $baris['bobot']) / $penyusun->sum('bobot'),
            0.0001,
        );
    }

    public function test_perkembangan_cpl_kumulatif_per_periode(): void
    {
        $perkembangan = $this->bukaDashboard()->viewData('perkembangan');

        $this->assertSame(['2025/2026 Genap', '2026/2027 Ganjil'], $perkembangan['periode']);
        // Urutan baris mengikuti kode CPL: CPL01, CPL02, CPL03.
        $this->assertSame(
            [[80.0, 66.67], [40.0, 40.0], [null, null]],
            array_map(fn (array $baris) => array_map(fn (?float $nilai) => $nilai === null ? null : round($nilai, 2), $baris), $perkembangan['nilai']),
        );
    }

    public function test_perkembangan_tidak_tampil_jika_kelas_baru_di_satu_periode(): void
    {
        $this->beriNilai($this->kelasGenap, $mahasiswaBaru = Mahasiswa::factory()->create(), ['UTS' => 70]);

        $response = $this->bukaDashboard(mahasiswa: $mahasiswaBaru)
            ->assertSee('Grafik perkembangan tampil jika kelas Anda di kurikulum ini tersebar di lebih dari satu periode.')
            ->assertDontSee('Kurikulum lain bisa dipilih');

        $this->assertNull($response->viewData('perkembangan'));
    }

    public function test_perkembangan_tetap_urut_periode_walau_kelas_lama_didaftarkan_belakangan(): void
    {
        // Kelas 2026/2027 Ganjil didaftarkan lebih dulu daripada kelas 2025/2026 Genap.
        $mahasiswaBaru = Mahasiswa::factory()->create();
        $kelasGanjil = KelasKuliah::where('mata_kuliah_id', $this->mataKuliahGanjil->mata_kuliah_id)->firstOrFail();
        $this->beriNilai($kelasGanjil, $mahasiswaBaru, ['Proyek' => 60]);
        $this->beriNilai($this->kelasGenap, $mahasiswaBaru, ['UTS' => 80, 'UAS' => 40]);

        $perkembangan = $this->bukaDashboard(mahasiswa: $mahasiswaBaru)->viewData('perkembangan');

        $this->assertSame(['2025/2026 Genap', '2026/2027 Ganjil'], $perkembangan['periode']);
        $this->assertSame(80.0, round($perkembangan['nilai'][0][0], 2));
        $this->assertSame(66.67, round($perkembangan['nilai'][0][1], 2));
    }

    public function test_mata_kuliah_yang_diulang_memakai_kelas_terbaru_yang_sudah_bernilai(): void
    {
        // Aisyah mengulang INF101 di 2026/2027 Ganjil, tetapi kelas barunya belum punya nilai.
        $kelasUlang = $this->buatKelas($this->mataKuliahGenap, [
            'UTS' => ['bobot' => 50, 'maks' => 100, 'kontribusi' => ['Sub-CPMK0111' => 50]],
            'UAS' => ['bobot' => 50, 'maks' => 100, 'kontribusi' => ['Sub-CPMK0211' => 50]],
        ], $this->periodeGanjil);
        $this->beriNilai($kelasUlang, $this->mahasiswa, []);

        $this->assertSame(
            ['CPL01' => [66.67, true], 'CPL02' => [40.0, false], 'CPL03' => [null, null]],
            $this->ringkasCpl($this->bukaDashboard()),
        );

        // Begitu kelas baru punya nilai, kelas itu yang dipakai: CPMK011 = 90, CPMK021 belum dinilai lagi.
        $this->beriNilai($kelasUlang, $this->mahasiswa, ['UTS' => 90]);
        $response = $this->bukaDashboard();

        // CPL01 = (90 × 50 + 60 × 100) / 150 = 70; CPL02 tidak lagi punya nilai di kelas terbaru.
        $this->assertSame(['CPL01' => [70.0, true], 'CPL02' => [null, null], 'CPL03' => [null, null]], $this->ringkasCpl($response));
        $this->assertSame([[80.0, 70.0], [40.0, null], [null, null]], array_map(
            fn (array $baris) => array_map(fn (?float $nilai) => $nilai === null ? null : round($nilai, 2), $baris),
            $response->viewData('perkembangan')['nilai'],
        ));

        // Rincian CPMK penyusun ikut memakai kelas terbaru yang sudah bernilai, jadi rata-rata
        // tertimbangnya tetap sama dengan capaian CPL01.
        $cpl01 = $response->viewData('daftarCpl')->firstWhere('kode', 'CPL01');
        $penyusun = collect($cpl01['penyusun']);
        $this->assertSame(
            [['CPMK011', 'INF101', '2026/2027 Ganjil', 90.0], ['CPMK012', 'INF103', '2026/2027 Ganjil', 60.0]],
            $penyusun->map(fn (array $baris) => [$baris['kode_cpmk'], $baris['kode_mata_kuliah'], $baris['periode'], round($baris['capaian'], 2)])->all(),
        );
        $this->assertEqualsWithDelta($cpl01['capaian'], $penyusun->sum(fn (array $baris) => $baris['capaian'] * $baris['bobot']) / $penyusun->sum('bobot'), 0.0001);
        $this->assertSame([], $response->viewData('daftarCpl')->firstWhere('kode', 'CPL02')['penyusun']);

        // Tabel Mata Kuliah yang Diikuti: kelas ulang baru dinilai 1 dari 2 asesmen.
        $response->assertSeeInOrder([
            'Mata Kuliah yang Diikuti',
            'INF101', '2026/2027 Ganjil', '1 dari 2',
            'INF103', '2026/2027 Ganjil', '1 dari 1',
            'INF101', '2025/2026 Genap', '2 dari 2',
        ]);
        $this->assertSame(2, $response->viewData('ringkasan')['jumlah_mata_kuliah']);
    }

    public function test_cpl_dipisah_per_kurikulum_dengan_bawaan_kurikulum_terbaru(): void
    {
        $kurikulumLama = Kurikulum::factory()->create(['tahun_berlaku' => 2021, 'nama_kurikulum' => 'Kurikulum 2021']);
        $kelasLama = $this->buatKelas(
            $this->buatMataKuliah($kurikulumLama, 'INF301', ['CPMK041' => ['cpl' => 'CPL01', 'bobot' => 100, 'sub' => ['Sub-CPMK0411' => 100]]]),
            ['UAS' => ['bobot' => 100, 'maks' => 100, 'kontribusi' => ['Sub-CPMK0411' => 100]]],
            $this->periodeGenap,
        );
        $this->beriNilai($kelasLama, $this->mahasiswa, ['UAS' => 30]);

        // Bawaan: kurikulum terbaru. CPL01 kurikulum 2021 (30) tidak digabung dengan CPL01 kurikulum 2026.
        $response = $this->bukaDashboard()->assertSee('Kurikulum 2021');
        $this->assertSame('Kurikulum OBE 2026', $response->viewData('kurikulum')->nama_kurikulum);
        $this->assertSame([66.67, true], $this->ringkasCpl($response)['CPL01']);

        // Di kurikulum 2021 kelasnya baru satu periode; keterangannya menyebut pilihan kurikulum lain.
        $response = $this->bukaDashboard(['kurikulum' => $kurikulumLama->kurikulum_id])
            ->assertSee('Kurikulum lain bisa dipilih di bagian atas halaman.');
        $this->assertSame(['CPL01' => [30.0, false]], $this->ringkasCpl($response));
        $response->assertSeeInOrder(['Mata Kuliah Diikuti', '>1</p>', '1 periode'], false);
        $this->assertSame(1, $response->viewData('ringkasan')['jumlah_mata_kuliah']);
    }

    public function test_kurikulum_yang_tidak_diikuti_tidak_bisa_dipilih_lewat_alamat(): void
    {
        $kurikulumLain = Kurikulum::factory()->create(['tahun_berlaku' => 2030, 'nama_kurikulum' => 'Kurikulum 2030']);
        $this->buatMataKuliah($kurikulumLain, 'INF901', ['CPMK901' => ['cpl' => 'CPL09', 'bobot' => 100, 'sub' => ['Sub-CPMK9011' => 100]]]);

        $response = $this->bukaDashboard(['kurikulum' => $kurikulumLain->kurikulum_id])
            ->assertOk()
            ->assertDontSee('Kurikulum 2030')
            ->assertDontSee('CPL09');

        $this->assertSame('Kurikulum OBE 2026', $response->viewData('kurikulum')->nama_kurikulum);
    }

    public function test_daftar_mata_kuliah_yang_diikuti_dengan_jumlah_asesmen_dinilai(): void
    {
        $this->bukaDashboard()
            ->assertSeeInOrder(['Mata Kuliah yang Diikuti', 'INF103', '2026/2027 Ganjil', '1 dari 1', 'INF101', '2025/2026 Genap', '2 dari 2'])
            ->assertSee('aria-label="Lihat capaian INF101 2025/2026 Genap"', false)
            // Tautan membuka halaman Capaian Pembelajaran pada periode dan mata kuliah kelas itu.
            ->assertSee('href="'.e(route('mahasiswa.capaian', [
                'periode' => $this->periodeGenap->periode_akademik_id,
                'mata_kuliah' => $this->mataKuliahGenap->mata_kuliah_id,
            ])).'"', false);
    }

    public function test_status_ambang_belum_ditetapkan(): void
    {
        config(['capaian.ambang_kelulusan_mahasiswa' => null]);

        $response = $this->bukaDashboard()
            ->assertSeeInOrder(['CPL Lulus', '–', 'Ambang belum ditetapkan'])
            ->assertDontSee('Belum Lulus')
            // Batang berwarna biru (netral) karena status Lulus tidak bisa ditentukan.
            ->assertSee('\u0022status\u0022:[\u0022netral\u0022,\u0022netral\u0022,\u0022kosong\u0022]', false);

        $this->assertNull($this->ringkasCpl($response)['CPL01'][1]);
    }

    public function test_dashboard_tidak_punya_aksi_ubah(): void
    {
        $this->bukaDashboard()
            ->assertDontSee('aria-label="Ubah', false)
            ->assertDontSee('aria-label="Hapus', false)
            ->assertDontSee('Simpan');
    }

    public function test_peran_lain_tidak_bisa_membuka_halaman_mahasiswa(): void
    {
        $this->masukSebagai(Role::DOSEN);

        $this->get(route('mahasiswa.dashboard'))->assertForbidden();
        $this->get(route('mahasiswa.capaian'))->assertForbidden();
    }

    public function test_mahasiswa_yang_belum_terdaftar_di_kelas(): void
    {
        $this->bukaDashboard(mahasiswa: Mahasiswa::factory()->create())
            ->assertOk()
            ->assertSee('Anda belum terdaftar di kelas mana pun.');
    }
}
