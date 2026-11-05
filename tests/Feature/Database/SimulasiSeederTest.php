<?php

namespace Tests\Feature\Database;

use App\Models\BahanKajian;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\MetodePenilaian;
use App\Models\NilaiAsesmen;
use App\Models\PeriodeAkademik;
use App\Models\PesertaKelas;
use App\Models\RencanaAsesmen;
use App\Models\Role;
use App\Models\User;
use App\Services\CapaianService;
use App\Services\DashboardMahasiswa;
use App\Services\MatriksBobotKelas;
use Database\Seeders\AkunDemoSeeder;
use Database\Seeders\DataMasterDemoSeeder;
use Database\Seeders\PemetaanDemoSeeder;
use Database\Seeders\PerkuliahanDemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SimulasiPerkuliahanSeeder;
use Database\Seeders\SimulasiSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * Data simulasi dua tahun (KD-77 sampai KD-80): riwayat perkuliahan 2024/2025 dan 2025/2026 yang
 * ditambahkan di atas data demo lewat SimulasiSeeder.
 */
class SimulasiSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['capaian.ambang_kelulusan_mahasiswa' => 50.01, 'capaian.ambang_ketercapaian' => 85]);
    }

    public function test_simulasi_menambah_riwayat_dua_tahun_dan_periode_aktif_tetap(): void
    {
        $this->isiDataSimulasi();

        $this->assertSame(
            ['2026/2027 Ganjil', '2025/2026 Genap', '2025/2026 Ganjil', '2024/2025 Genap', '2024/2025 Ganjil'],
            PeriodeAkademik::urutTerbaru()->get()->map->nama()->all(),
        );
        $this->assertSame('2026/2027 Ganjil', PeriodeAkademik::aktif()->nama());

        // 5 kelas demo + 14 kelas simulasi; 24 mahasiswa demo + 16 mahasiswa angkatan 2022 dan 2025.
        $this->assertDatabaseCount('kelas_kuliah', 19);
        $this->assertDatabaseCount('mahasiswa', 40);
        $this->assertSame([2022, 2023, 2024, 2025, 2026], Mahasiswa::distinct()->orderBy('angkatan')->pluck('angkatan')->all());

        // Kurikulum 2021 bertambah 6 MK dan 3 CPMK; kurikulum 2026 tidak berubah.
        $this->assertSame(9, $this->kurikulum(2021)->mataKuliah()->count());
        $this->assertSame(3, $this->kurikulum(2026)->mataKuliah()->count());
        $this->assertDatabaseCount('cpmk', 21);

        // Mahasiswa simulasi seperti mahasiswa contoh lain: tanpa email, login dengan NPM dan
        // mahasiswa123 (bagian 10).
        foreach (['9992200001', '9992500008'] as $nim) {
            $user = Mahasiswa::where('nim', $nim)->firstOrFail()->user;

            $this->assertNull($user->email);
            $this->assertTrue(Hash::check('mahasiswa123', $user->password));
            $this->assertTrue($user->punyaPeran(Role::MAHASISWA));
        }

        // Kurikulum 2026 baru dipakai mulai 2026/2027, jadi semua kelas sebelumnya dari kurikulum 2021.
        $this->assertSame(
            [2021],
            KelasKuliah::with(['mataKuliah.kurikulum', 'periodeAkademik'])->get()
                ->filter(fn (KelasKuliah $kelas) => $kelas->periodeAkademik->tahun_akademik < '2026/2027')
                ->pluck('mataKuliah.kurikulum.tahun_berlaku')->unique()->values()->all(),
        );
    }

    public function test_bobot_kurikulum_dan_rencana_asesmen_simulasi_memenuhi_aturan_5_1(): void
    {
        $this->isiDataSimulasi();

        foreach (MataKuliah::with('pemetaanCpmkMk.subCpmk')->get() as $mataKuliah) {
            $this->assertEqualsWithDelta(100, $mataKuliah->totalBobotCpmk(), 0.001, "Total bobot CPMK {$mataKuliah->kode_mata_kuliah} bukan 100.");

            foreach ($mataKuliah->pemetaanCpmkMk as $pemetaan) {
                $this->assertEqualsWithDelta((float) $pemetaan->bobot_cpmk, $pemetaan->totalBobotSubCpmk(), 0.001, "Bobot Sub-CPMK {$pemetaan->label()} tidak sama dengan bobot CPMK-nya.");
            }
        }

        foreach (KelasKuliah::all() as $kelas) {
            $matriks = new MatriksBobotKelas($kelas);

            $this->assertNotEmpty($matriks->daftarAsesmen, "Kelas {$kelas->nama()} belum punya rencana asesmen.");
            $this->assertSame([], $matriks->peringatan(), "Rencana asesmen {$kelas->nama()} memunculkan peringatan.");
            $this->assertEqualsWithDelta(100, $matriks->totalBobotAsesmen(), 0.001);

            foreach ($matriks->daftarAsesmen as $asesmen) {
                $this->assertEqualsWithDelta((float) $asesmen->bobot_asesmen, $matriks->totalAsesmen($asesmen), 0.001, "Kontribusi {$asesmen->nama_asesmen} {$kelas->nama()} tidak sama dengan bobotnya.");
            }
        }
    }

    public function test_nilai_dalam_batas_dan_tanggal_asesmen_di_dalam_semesternya(): void
    {
        $this->isiDataSimulasi();

        // Nilai demo 162 ditambah nilai simulasi 483 (semua asesmen × peserta, dikurangi 2 yang tidak ikut).
        $this->assertDatabaseCount('rencana_asesmen', 21 + 59);
        $this->assertDatabaseCount('nilai_asesmen', 162 + 483);

        foreach (NilaiAsesmen::with('rencanaAsesmen')->get() as $nilai) {
            $this->assertGreaterThanOrEqual(0, (float) $nilai->nilai);
            $this->assertLessThanOrEqual((float) $nilai->rencanaAsesmen->nilai_maksimum, (float) $nilai->nilai);
        }

        // Ganjil berjalan September–Januari, Genap Februari–Juli.
        foreach (RencanaAsesmen::with('kelasKuliah.periodeAkademik')->get() as $asesmen) {
            $periode = $asesmen->kelasKuliah->periodeAkademik;
            $tahunAwal = (int) substr($periode->tahun_akademik, 0, 4);
            [$mulai, $selesai] = $periode->semester_periode === PeriodeAkademik::GANJIL
                ? [Carbon::create($tahunAwal, 9, 1), Carbon::create($tahunAwal + 1, 1, 31)]
                : [Carbon::create($tahunAwal + 1, 2, 1), Carbon::create($tahunAwal + 1, 7, 31)];

            $this->assertTrue(
                $asesmen->tanggal_asesmen->between($mulai, $selesai),
                "Tanggal {$asesmen->nama_asesmen} ({$asesmen->tanggal_asesmen->toDateString()}) di luar {$periode->nama()}.",
            );
            // Kuliah dimulai hari Senin, jadi asesmen tidak jatuh pada akhir pekan.
            $this->assertFalse($asesmen->tanggal_asesmen->isWeekend(), "{$asesmen->nama_asesmen} {$periode->nama()} jatuh pada akhir pekan.");
        }
    }

    public function test_perkembangan_lima_periode_dan_jarak_hendra_dengan_kartika_melebar(): void
    {
        $this->isiDataSimulasi();

        $hendra = app(DashboardMahasiswa::class)->susun(Mahasiswa::where('nim', '9992400001')->firstOrFail(), null);

        $this->assertSame(
            ['2024/2025 Ganjil', '2024/2025 Genap', '2025/2026 Ganjil', '2025/2026 Genap', '2026/2027 Ganjil'],
            $hendra['perkembangan']['periode'],
        );
        $this->assertSame(6, $hendra['ringkasan']['jumlah_mata_kuliah']);

        // Hendra makin baik setiap semester: setiap CPL-nya berakhir lebih tinggi dari nilai pertamanya.
        foreach ($hendra['perkembangan']['nilai'] as $urutan => $nilaiPerPeriode) {
            $terisi = array_values(array_filter($nilaiPerPeriode, fn (?float $nilai) => $nilai !== null));

            $this->assertGreaterThan($terisi[0], end($terisi), "{$hendra['daftarCpl'][$urutan]['kode']} Hendra tidak naik.");
        }

        // Kemampuan Hendra naik 4 poin per semester, sedangkan Kartika turun 2 poin. Di semester pertama
        // nilai akhir keduanya hampir sama; di semester keempat Hendra jauh di atas Kartika.
        $selisihAwal = $this->nilaiAkhir('9992400001', 'INF103', '2024/2025 Ganjil') - $this->nilaiAkhir('9992400004', 'INF103', '2024/2025 Ganjil');
        $selisihAkhir = $this->nilaiAkhir('9992400001', 'INF204', '2025/2026 Genap') - $this->nilaiAkhir('9992400004', 'INF204', '2025/2026 Genap');
        $this->assertLessThan(8, abs($selisihAwal));
        $this->assertGreaterThan(12, $selisihAkhir);
    }

    public function test_semua_mahasiswa_kurikulum_2021_punya_grafik_perkembangan(): void
    {
        $this->isiDataSimulasi();
        $kurikulumId = $this->kurikulum(2021)->kurikulum_id;

        // Angkatan 2023 dan 2024 punya kelas di kelima periode; angkatan 2022 dan 2025 di dua periode
        // atau lebih. Salah ketik jadwal di SimulasiPerkuliahanSeeder akan tertangkap di sini.
        foreach (Mahasiswa::whereIn('angkatan', [2022, 2023, 2024, 2025])->get() as $mahasiswa) {
            $perkembangan = app(DashboardMahasiswa::class)->susun($mahasiswa, $kurikulumId)['perkembangan'];

            $this->assertNotNull($perkembangan, "Grafik perkembangan {$mahasiswa->nim} kosong.");

            if (in_array($mahasiswa->angkatan, [2023, 2024], true)) {
                $this->assertCount(5, $perkembangan['periode'], "Riwayat {$mahasiswa->nim} bukan lima periode.");
            }
        }
    }

    public function test_mahasiswa_yang_mengulang_memakai_kelas_terbaru(): void
    {
        $this->isiDataSimulasi();

        $nanda = Mahasiswa::where('nim', '9992400007')->firstOrFail();
        $daftarPeserta = PesertaKelas::with(CapaianService::RELASI_PESERTA)->where('mahasiswa_id', $nanda->mahasiswa_id)->get();
        $kodeMataKuliah = fn (PesertaKelas $peserta) => $peserta->kelasKuliah->mataKuliah->kode_mata_kuliah;

        // Nanda mengikuti INF103 dua kali; yang dipakai kelas 2025/2026 Ganjil (bagian 5.4).
        $this->assertCount(2, $daftarPeserta->filter(fn (PesertaKelas $peserta) => $kodeMataKuliah($peserta) === 'INF103'));
        $this->assertSame(
            '2025/2026 Ganjil',
            app(CapaianService::class)->pilihKelasPerMataKuliah($daftarPeserta)
                ->first(fn (PesertaKelas $peserta) => $kodeMataKuliah($peserta) === 'INF103')
                ->kelasKuliah->periodeAkademik->nama(),
        );

        // Setelah mengulang, CPL02 Nanda naik dari di bawah AM menjadi Lulus.
        $cpl02 = app(DashboardMahasiswa::class)->susun($nanda, null)['perkembangan']['nilai'][1];
        $this->assertLessThan(50.01, $cpl02[1]);
        $this->assertGreaterThanOrEqual(50.01, $cpl02[2]);
    }

    public function test_simulasi_tidak_mengubah_data_demo_maupun_mahasiswa_lain(): void
    {
        $this->seed();
        $nilaiDemo = $this->semuaNilai();
        $rencanaDemo = $this->semuaRencanaAsesmen();
        $pesertaDemo = $this->pesertaKelasDemo();
        // Mahasiswa yang bukan mahasiswa contoh (NPM tidak diawali 999), mis. dimasukkan lewat aplikasi.
        $mahasiswaLain = Mahasiswa::factory()->create(['nim' => '0735241101', 'angkatan' => 2024]);

        $this->seed(SimulasiSeeder::class);

        // Simulasi hanya menambah data: nilai, rencana asesmen, dan peserta kelas demo tetap sama.
        $this->assertSame($nilaiDemo, array_intersect_key($this->semuaNilai(), $nilaiDemo));
        $this->assertSame($rencanaDemo, array_intersect_key($this->semuaRencanaAsesmen(), $rencanaDemo));
        $this->assertSame($pesertaDemo, $this->pesertaKelasDemo());
        $this->assertSame(0, $mahasiswaLain->pesertaKelas()->count());

        // Urutan kemampuan hanya dihitung dari mahasiswa contoh: Hendra tetap urutan pertama angkatan 2024
        // (kemampuan 88, nilai akhir INF204 91,8). Jika mahasiswa lain ikut dihitung, nilainya turun 6 poin.
        $hendra = PesertaKelas::whereHas('mahasiswa', fn ($query) => $query->where('nim', '9992400001'))
            ->whereHas('kelasKuliah', fn ($query) => $query->where('kelas_kuliah_id', SimulasiPerkuliahanSeeder::cariKelas('INF204', '2025/2026', PeriodeAkademik::GENAP)->kelas_kuliah_id))
            ->firstOrFail();
        $this->assertEqualsWithDelta(91.8, app(CapaianService::class)->hitungCapaianPeserta($hendra->load(CapaianService::RELASI_PESERTA))['nilai_akhir'], 0.001);
    }

    public function test_seeder_demo_dan_simulasi_bisa_dijalankan_ulang_tanpa_mengubah_data(): void
    {
        $this->isiDataSimulasi();

        // Dosen menambah asesmen sendiri di kelas simulasi dan mengisi nilainya lewat aplikasi.
        $kelas = SimulasiPerkuliahanSeeder::cariKelas('INF204', '2025/2026', PeriodeAkademik::GENAP);
        $asesmenDosen = RencanaAsesmen::create([
            'kelas_kuliah_id' => $kelas->kelas_kuliah_id,
            'metode_penilaian_id' => MetodePenilaian::where('kode_metode', 'KUIS')->value('metode_penilaian_id'),
            'nama_asesmen' => 'Kuis Tambahan',
            'bobot_asesmen' => 0,
            'nilai_maksimum' => 100,
            'tanggal_asesmen' => '2026-05-04',
        ]);
        $asesmenDosen->simpanNilai([$kelas->pesertaKelas()->value('peserta_kelas_id') => 55]);

        // Admin memasukkan mahasiswa yang bukan mahasiswa contoh ke kelas simulasi.
        $mahasiswaLain = Mahasiswa::factory()->create(['nim' => '0735251101', 'angkatan' => 2025]);
        SimulasiPerkuliahanSeeder::cariKelas('INF105', '2025/2026', PeriodeAkademik::GENAP)->mahasiswa()->attach($mahasiswaLain->mahasiswa_id);

        $jumlahAwal = $this->hitungBaris();
        $nilaiAwal = $this->semuaNilai();
        $rencanaAwal = $this->semuaRencanaAsesmen();

        // Seeder demo dijalankan ulang (mis. lewat php artisan db:seed): kelas simulasi tidak ikut diubah.
        $this->seed();
        $this->assertSame($jumlahAwal, $this->hitungBaris());
        $this->assertSame($nilaiAwal, $this->semuaNilai());
        $this->assertSame($rencanaAwal, $this->semuaRencanaAsesmen());

        // Simulasi dijalankan ulang: tidak ada data ganda, nilainya tetap sama, nilai asesmen buatan dosen
        // tidak ditimpa, dan mahasiswa yang bukan mahasiswa contoh tidak diberi nilai simulasi.
        $this->seed(SimulasiSeeder::class);
        $this->assertSame($jumlahAwal, $this->hitungBaris());
        $this->assertSame($nilaiAwal, $this->semuaNilai());
        $this->assertSame($rencanaAwal, $this->semuaRencanaAsesmen());
        $this->assertSame(0, NilaiAsesmen::whereHas('pesertaKelas', fn ($query) => $query->where('mahasiswa_id', $mahasiswaLain->mahasiswa_id))->count());
    }

    public function test_simulasi_menolak_berjalan_tanpa_data_demo_lengkap(): void
    {
        // Database kosong.
        $this->assertSimulasiDitolak();

        // Data master dan pemetaan demo sudah ada, tetapi kelas demo belum.
        $this->seed([RoleSeeder::class, AkunDemoSeeder::class, DataMasterDemoSeeder::class, PemetaanDemoSeeder::class]);
        $this->assertSimulasiDitolak();
        $this->assertFalse(MataKuliah::where('kode_mata_kuliah', 'INF103')->exists(), 'Simulasi yang ditolak tidak boleh menulis data.');
    }

    public function test_simulasi_yang_gagal_di_tengah_tidak_meninggalkan_data_setengah_jadi(): void
    {
        $this->seed();

        // BK04 Kurikulum 2021 dihapus lewat aplikasi, padahal dipakai pemetaan MK simulasi INF203.
        BahanKajian::where('kode_bk', 'BK04')->whereHas('kurikulum', fn ($query) => $query->where('tahun_berlaku', 2021))->delete();
        $jumlahAwal = $this->hitungBaris();

        try {
            $this->seed(SimulasiSeeder::class);
            $this->fail('SimulasiSeeder seharusnya gagal karena BK04 tidak ada.');
        } catch (QueryException) {
            // Gagal saat memetakan INF203 ke BK04, setelah beberapa MK simulasi sempat dibuat.
        }

        // Semua langkah dibatalkan oleh transaksi: tidak ada MK, periode, atau kelas simulasi yang tersisa.
        $this->assertSame($jumlahAwal, $this->hitungBaris());
        $this->assertFalse(MataKuliah::where('kode_mata_kuliah', 'INF103')->exists());
    }

    public function test_dashboard_dan_laporan_bisa_dibuka_dengan_data_simulasi(): void
    {
        $this->isiDataSimulasi();
        $kurikulum2021 = $this->kurikulum(2021);
        $periodeAwal = PeriodeAkademik::where('tahun_akademik', '2024/2025')->where('semester_periode', PeriodeAkademik::GANJIL)->firstOrFail();

        // Koprodi: laporan CPL kurikulum 2021 untuk periode pertama simulasi saja.
        $this->masukSebagai(Role::KOPRODI, User::cariBerdasarkanIdentitas('koprodi@simonev.test'));
        $response = $this->get(route('koprodi.laporan', ['kurikulum' => $kurikulum2021->kurikulum_id, 'periode' => $periodeAwal->periode_akademik_id, 'tab' => 'cpl']))
            ->assertOk()
            ->assertSee('CPL dihitung dari kelas pada periode ini saja.');
        $this->assertSame(['CPL01', 'CPL02', 'CPL03', 'CPL04'], collect($response->viewData('hasil')['baris'])->pluck('kode')->all());
        $this->assertNotContains(null, collect($response->viewData('hasil')['baris'])->pluck('rekap.rata_rata')->all());

        $this->get(route('koprodi.dashboard', ['kurikulum' => $kurikulum2021->kurikulum_id, 'angkatan' => 2022]))
            ->assertOk()
            ->assertSee('Angkatan 2022');

        // Mahasiswa: dashboard Hendra memuat grafik perkembangan lima periode.
        $this->masukSebagai(Role::MAHASISWA, User::cariBerdasarkanIdentitas('9992400001'));
        $response = $this->get(route('mahasiswa.dashboard'))
            ->assertOk()
            ->assertSee('Perkembangan CPL per Periode');
        $this->assertCount(5, $response->viewData('perkembangan')['periode']);
    }

    /**
     * SimulasiSeeder harus berhenti dengan pesan pengaman, tanpa menulis data apa pun.
     */
    private function assertSimulasiDitolak(): void
    {
        try {
            $this->app->make(SimulasiSeeder::class)->run();
            $this->fail('SimulasiSeeder seharusnya menolak berjalan.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Data demo belum ada atau tidak lengkap', $exception->getMessage());
        }
    }

    private function isiDataSimulasi(): void
    {
        $this->seed();
        $this->seed(SimulasiSeeder::class);
    }

    private function kurikulum(int $tahunBerlaku): Kurikulum
    {
        return Kurikulum::where('tahun_berlaku', $tahunBerlaku)->firstOrFail();
    }

    /**
     * Nilai akhir satu mahasiswa di kelas simulasi sebuah MK pada periode tertentu.
     */
    private function nilaiAkhir(string $nim, string $kodeMataKuliah, string $namaPeriode): float
    {
        $peserta = PesertaKelas::with(CapaianService::RELASI_PESERTA)
            ->whereHas('mahasiswa', fn ($query) => $query->where('nim', $nim))
            ->get()
            ->firstOrFail(fn (PesertaKelas $peserta) => $peserta->kelasKuliah->mataKuliah->kode_mata_kuliah === $kodeMataKuliah
                && $peserta->kelasKuliah->periodeAkademik->nama() === $namaPeriode);

        return app(CapaianService::class)->hitungCapaianPeserta($peserta)['nilai_akhir'];
    }

    /**
     * @return array<string, string> id nilai => nilai
     */
    private function semuaNilai(): array
    {
        return NilaiAsesmen::orderBy('nilai_asesmen_id')->pluck('nilai', 'nilai_asesmen_id')->all();
    }

    /**
     * @return array<string, string> id rencana asesmen => "bobot|nilai maks|tanggal"
     */
    private function semuaRencanaAsesmen(): array
    {
        return RencanaAsesmen::orderBy('rencana_asesmen_id')->get()
            ->mapWithKeys(fn (RencanaAsesmen $asesmen) => [
                $asesmen->rencana_asesmen_id => "{$asesmen->bobot_asesmen}|{$asesmen->nilai_maksimum}|{$asesmen->tanggal_asesmen->toDateString()}",
            ])
            ->all();
    }

    /**
     * @return array<string, int> id kelas demo => jumlah peserta
     */
    private function pesertaKelasDemo(): array
    {
        return KelasKuliah::whereIn('kelas_kuliah_id', PerkuliahanDemoSeeder::idKelasDemo())
            ->withCount('pesertaKelas')
            ->orderBy('kelas_kuliah_id')
            ->pluck('peserta_kelas_count', 'kelas_kuliah_id')
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function hitungBaris(): array
    {
        $daftarTabel = [
            'users', 'user_roles', 'mahasiswa', 'cpmk', 'mata_kuliah', 'pemetaan_bk_mk', 'pemetaan_cpmk_mk',
            'sub_cpmk', 'sub_cpmk_penilaian', 'periode_akademik', 'kelas_kuliah', 'dosen_kelas', 'peserta_kelas',
            'rencana_asesmen', 'rencana_asesmen_sub_cpmk', 'nilai_asesmen',
        ];

        return collect($daftarTabel)->mapWithKeys(fn (string $tabel) => [$tabel => DB::table($tabel)->count()])->all();
    }
}
