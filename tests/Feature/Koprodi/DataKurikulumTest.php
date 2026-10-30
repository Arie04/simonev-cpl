<?php

namespace Tests\Feature\Koprodi;

use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\EvaluasiKurikulum;
use App\Models\Kurikulum;
use App\Models\PemetaanCpmkMk;
use App\Models\ProfilLulusan;
use App\Models\Role;
use App\Models\SubCpmk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Data Kurikulum untuk Koprodi: read-only, dengan pemilih kurikulum (bagian 7.6.12 spesifikasi).
 */
class DataKurikulumTest extends TestCase
{
    use RefreshDatabase;

    public static function daftarTab(): array
    {
        return collect(['ringkasan', 'evaluasi', 'profil-lulusan', 'cpl', 'bahan-kajian', 'mata-kuliah', 'cpmk', 'sub-cpmk', 'pemetaan'])
            ->mapWithKeys(fn (string $tab) => [$tab => [$tab]])
            ->all();
    }

    #[DataProvider('daftarTab')]
    public function test_koprodi_membuka_setiap_tab_tanpa_tombol_ubah_atau_hapus(string $tab): void
    {
        $subCpmk = SubCpmk::factory()->create();
        $kurikulum = $subCpmk->pemetaanCpmkMk->mataKuliah->kurikulum;
        EvaluasiKurikulum::factory()->for($kurikulum)->create();
        $this->masukSebagai(Role::KOPRODI);

        $this->get(route('koprodi.kurikulum', ['kurikulum' => $kurikulum->kurikulum_id, 'tab' => $tab]))
            ->assertOk()
            ->assertSee($kurikulum->nama_kurikulum)
            ->assertDontSee('aria-label="Ubah', false)
            ->assertDontSee('aria-label="Hapus', false)
            ->assertDontSee('Simpan Pemetaan');
    }

    public function test_koprodi_melihat_evaluasi_kurikulum_dan_matriks_pemetaan(): void
    {
        $kurikulum = Kurikulum::factory()->create(['tahun_berlaku' => 2026]);
        EvaluasiKurikulum::factory()->for($kurikulum)->create(['tahap_evaluasi' => 'Evaluasi kurikulum 2021']);
        $cpl = Cpl::factory()->for($kurikulum)->create(['kode_cpl' => 'CPL01']);
        $cpl->profilLulusan()->attach(ProfilLulusan::factory()->for($kurikulum)->create(['kode_pl' => 'PL01']));
        $this->masukSebagai(Role::KOPRODI);

        $this->get(route('koprodi.kurikulum', ['tab' => 'evaluasi']))->assertSee('Evaluasi kurikulum 2021');

        $this->get(route('koprodi.kurikulum', ['tab' => 'pemetaan', 'jenis' => 'cpl-pl']))
            ->assertSee('CPL01')
            ->assertSee('PL01')
            ->assertSee('CPL01 – PL01 terhubung');
    }

    public function test_tab_mata_kuliah_menampilkan_ringkasan_bobot(): void
    {
        $kurikulum = Kurikulum::factory()->create(['tahun_berlaku' => 2026]);
        $pemetaan = PemetaanCpmkMk::factory()
            ->for(Cpmk::factory()->for(Cpl::factory()->for($kurikulum))->state(['kode_cpmk' => 'CPMK051']))
            ->create(['bobot_cpmk' => 100]);
        SubCpmk::factory()->for($pemetaan)->create(['kode_sub_cpmk' => 'Sub-CPMK0511', 'bobot_sub_cpmk' => 100]);
        $this->masukSebagai(Role::KOPRODI);

        $this->get(route('koprodi.kurikulum', ['tab' => 'mata-kuliah']))
            ->assertSee($pemetaan->mataKuliah->nama_mata_kuliah)
            ->assertSeeInOrder(['CPMK051', 'Sub-CPMK0511'])
            ->assertDontSee('seharusnya 100');
    }

    public function test_koprodi_tidak_bisa_membuka_halaman_ubah_milik_admin(): void
    {
        $this->masukSebagai(Role::KOPRODI);

        $this->get(route('admin.pemetaan.edit'))->assertForbidden();
        $this->get(route('admin.cpl.index'))->assertForbidden();
    }

    public function test_peran_lain_tidak_bisa_membuka_data_kurikulum_koprodi(): void
    {
        foreach ([Role::ADMIN, Role::DOSEN, Role::MAHASISWA] as $kodeRole) {
            $this->masukSebagai($kodeRole);

            $this->get(route('koprodi.kurikulum'))->assertForbidden();
        }
    }
}
