<?php

namespace Tests\Feature\Dosen;

use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\MataKuliah;
use App\Models\PemetaanCpmkMk;
use App\Models\PeriodeAkademik;
use App\Models\Role;
use App\Models\SubCpmk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard Dosen dan halaman Mata Kuliah Dosen (bagian 7.6.11 spesifikasi, KF-07).
 */
class DashboardDanMataKuliahDosenTest extends TestCase
{
    use RefreshDatabase;

    private Dosen $dosen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dosen = Dosen::factory()->create();
        $this->masukSebagai(Role::DOSEN, $this->dosen->user);
    }

    public function test_dashboard_menampilkan_kelas_yang_diampu_pada_periode_aktif(): void
    {
        $periodeAktif = PeriodeAkademik::factory()->create(['tahun_akademik' => '2026/2027', 'semester_periode' => 'Ganjil']);
        $periodeLalu = PeriodeAkademik::factory()->create(['tahun_akademik' => '2025/2026', 'semester_periode' => 'Genap']);
        $kelasAktif = KelasKuliah::factory()->for($periodeAktif)->for(MataKuliah::factory()->state(['nama_mata_kuliah' => 'Algoritma Aktif']))->create();
        $kelasLalu = KelasKuliah::factory()->for($periodeLalu)->for(MataKuliah::factory()->state(['nama_mata_kuliah' => 'Struktur Data Lalu']))->create();
        $kelasDosenLain = KelasKuliah::factory()->for($periodeAktif)->for(MataKuliah::factory()->state(['nama_mata_kuliah' => 'Kelas Dosen Lain']))->create();
        $kelasAktif->dosen()->attach($this->dosen);
        $kelasLalu->dosen()->attach($this->dosen);
        $kelasDosenLain->dosen()->attach(Dosen::factory()->create());

        $this->get(route('dosen.dashboard'))
            ->assertOk()
            ->assertSee('periode 2026/2027 Ganjil')
            ->assertSee('Algoritma Aktif')
            ->assertDontSee('Struktur Data Lalu')
            ->assertDontSee('Kelas Dosen Lain');
    }

    public function test_dashboard_kosong_jika_tidak_mengampu_kelas(): void
    {
        KelasKuliah::factory()->for(PeriodeAkademik::factory()->state(['tahun_akademik' => '2026/2027', 'semester_periode' => 'Ganjil']))->create();

        $this->get(route('dosen.dashboard'))->assertSee('Anda belum mengampu kelas pada periode 2026/2027 Ganjil.');
    }

    public function test_daftar_kelas_rencana_asesmen_hanya_berisi_kelas_yang_diampu(): void
    {
        $kelasDiampu = KelasKuliah::factory()->for(MataKuliah::factory()->state(['nama_mata_kuliah' => 'Kelas Saya']))->create();
        KelasKuliah::factory()->for(MataKuliah::factory()->state(['nama_mata_kuliah' => 'Kelas Orang Lain']))->create();
        $kelasDiampu->dosen()->attach($this->dosen);

        $this->get(route('dosen.rencana-asesmen'))
            ->assertOk()
            ->assertSee('Kelas Saya')
            ->assertDontSee('Kelas Orang Lain');
    }

    public function test_dosen_melihat_cpl_cpmk_dan_sub_cpmk_mata_kuliah_yang_diampu(): void
    {
        $kelas = KelasKuliah::factory()->create();
        $kelas->dosen()->attach($this->dosen);
        $cpl = Cpl::factory()->for($kelas->mataKuliah->kurikulum)->create(['kode_cpl' => 'CPL04', 'deskripsi_cpl' => 'Mampu merancang algoritma']);
        $pemetaan = PemetaanCpmkMk::factory()
            ->for(Cpmk::factory()->for($cpl)->state(['kode_cpmk' => 'CPMK041']))
            ->for($kelas->mataKuliah)
            ->create(['bobot_cpmk' => 100]);
        SubCpmk::factory()->for($pemetaan)->create(['kode_sub_cpmk' => 'Sub-CPMK0411', 'bobot_sub_cpmk' => 100]);

        $this->get(route('dosen.mata-kuliah.index'))->assertSee($kelas->mataKuliah->kode_mata_kuliah);

        $this->get(route('dosen.mata-kuliah.show', $kelas->mataKuliah))
            ->assertOk()
            ->assertSee('Mampu merancang algoritma')
            ->assertSeeInOrder(['CPMK041', 'Sub-CPMK0411']);
    }

    public function test_dosen_tidak_bisa_melihat_mata_kuliah_yang_tidak_diampu(): void
    {
        $mataKuliah = MataKuliah::factory()->create();

        $this->get(route('dosen.mata-kuliah.show', $mataKuliah))->assertForbidden();
    }

    public function test_peran_lain_tidak_bisa_membuka_halaman_dosen(): void
    {
        foreach ([Role::ADMIN, Role::KOPRODI, Role::MAHASISWA] as $kodeRole) {
            $this->masukSebagai($kodeRole);

            $this->get(route('dosen.mata-kuliah.index'))->assertForbidden();
            $this->get(route('dosen.rencana-asesmen'))->assertForbidden();
        }
    }
}
