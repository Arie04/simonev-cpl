<?php

namespace Tests\Feature\Admin;

use App\Models\BahanKajian;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use App\Models\MetodePenilaian;
use App\Models\PemetaanCpmkMk;
use App\Models\ProfilLulusan;
use App\Models\ProgramStudi;
use App\Models\RencanaAsesmen;
use App\Models\Role;
use App\Models\SubCpmk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menghapus data yang masih dipakai data lain ditolak dengan pesan jelas (bagian 6
 * spesifikasi, KD-20). Pesannya tampil sebagai toast dari session 'gagal'.
 */
class HapusDataMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masukSebagai(Role::ADMIN);
    }

    public function test_data_yang_tidak_dipakai_bisa_dihapus(): void
    {
        $cpl = Cpl::factory()->create();

        $this->delete(route('admin.cpl.destroy', $cpl))
            ->assertRedirect(route('admin.cpl.index'))
            ->assertSessionHas('sukses', 'CPL berhasil dihapus.');

        $this->assertModelMissing($cpl);
    }

    public function test_program_studi_yang_punya_kurikulum_tidak_bisa_dihapus(): void
    {
        $programStudi = ProgramStudi::factory()->has(Kurikulum::factory())->create();

        $this->delete(route('admin.program-studi.destroy', $programStudi))
            ->assertSessionHas('gagal', 'Program studi tidak bisa dihapus karena masih memiliki kurikulum.');

        $this->assertModelExists($programStudi);
    }

    public function test_kurikulum_yang_masih_berisi_data_tidak_bisa_dihapus(): void
    {
        $kurikulum = Kurikulum::factory()->create();
        Cpl::factory()->for($kurikulum)->create();
        MataKuliah::factory()->for($kurikulum)->create();

        $this->delete(route('admin.kurikulum.destroy', $kurikulum))
            ->assertSessionHas('gagal', 'Kurikulum tidak bisa dihapus karena masih memiliki data CPL, mata kuliah.');

        $this->assertModelExists($kurikulum);
    }

    public function test_cpl_yang_punya_cpmk_tidak_bisa_dihapus(): void
    {
        $cpmk = Cpmk::factory()->create();

        $this->delete(route('admin.cpl.destroy', $cpmk->cpl))
            ->assertSessionHas('gagal', 'CPL tidak bisa dihapus karena masih memiliki CPMK.');

        $this->assertModelExists($cpmk->cpl);
    }

    public function test_mata_kuliah_yang_dipakai_kelas_tidak_bisa_dihapus(): void
    {
        $kelas = KelasKuliah::factory()->create();

        $this->delete(route('admin.mata-kuliah.destroy', $kelas->mataKuliah))
            ->assertSessionHas('gagal', 'Mata kuliah tidak bisa dihapus karena masih dipakai kelas kuliah.');
    }

    public function test_cpmk_dan_mata_kuliah_yang_pemetaannya_punya_sub_cpmk_tidak_bisa_dihapus(): void
    {
        $subCpmk = SubCpmk::factory()->create();
        $pemetaan = $subCpmk->pemetaanCpmkMk;

        $this->delete(route('admin.cpmk.destroy', $pemetaan->cpmk))
            ->assertSessionHas('gagal', 'CPMK tidak bisa dihapus karena pemetaannya ke mata kuliah masih memiliki Sub-CPMK.');

        $this->delete(route('admin.mata-kuliah.destroy', $pemetaan->mataKuliah))
            ->assertSessionHas('gagal', 'Mata kuliah tidak bisa dihapus karena pemetaan CPMK-nya masih memiliki Sub-CPMK.');

        $this->assertModelExists($pemetaan);
    }

    public function test_menghapus_cpmk_tanpa_sub_cpmk_ikut_menghapus_pemetaannya(): void
    {
        $pemetaan = PemetaanCpmkMk::factory()->create();

        $this->delete(route('admin.cpmk.destroy', $pemetaan->cpmk))->assertSessionHas('sukses');

        $this->assertModelMissing($pemetaan);
        $this->assertModelExists($pemetaan->mataKuliah);
    }

    public function test_menghapus_profil_lulusan_dan_bahan_kajian_ikut_menghapus_pemetaannya(): void
    {
        $cpl = Cpl::factory()->create();
        $profilLulusan = ProfilLulusan::factory()->for($cpl->kurikulum)->create();
        $bahanKajian = BahanKajian::factory()->for($cpl->kurikulum)->create();
        $cpl->profilLulusan()->attach($profilLulusan);
        $cpl->bahanKajian()->attach($bahanKajian);

        $this->delete(route('admin.profil-lulusan.destroy', $profilLulusan))->assertSessionHas('sukses');
        $this->delete(route('admin.bahan-kajian.destroy', $bahanKajian))->assertSessionHas('sukses');

        $this->assertDatabaseCount('pemetaan_cpl_pl', 0);
        $this->assertDatabaseCount('pemetaan_cpl_bk', 0);
        $this->assertModelExists($cpl);
    }

    public function test_metode_penilaian_yang_dipakai_rencana_asesmen_tidak_bisa_dihapus(): void
    {
        $rencanaAsesmen = RencanaAsesmen::factory()->create();

        $this->delete(route('admin.metode-penilaian.destroy', $rencanaAsesmen->metodePenilaian))
            ->assertSessionHas('gagal', 'Metode penilaian tidak bisa dihapus karena masih dipakai rencana asesmen.');
    }

    public function test_metode_penilaian_yang_dipakai_rincian_penilaian_sub_cpmk_tidak_bisa_dihapus(): void
    {
        $subCpmk = SubCpmk::factory()->create();
        $metode = MetodePenilaian::factory()->create();
        $subCpmk->metodePenilaian()->attach($metode, ['teknik_penilaian' => 'Tes tertulis']);

        $this->delete(route('admin.metode-penilaian.destroy', $metode))
            ->assertSessionHas('gagal', 'Metode penilaian tidak bisa dihapus karena masih dipakai rincian penilaian Sub-CPMK.');
    }

    public function test_cpl_dari_cpmk_yang_sudah_dipetakan_tidak_bisa_diganti(): void
    {
        $pemetaan = PemetaanCpmkMk::factory()->create();
        $cpmk = $pemetaan->cpmk;
        $cplLain = Cpl::factory()->for($cpmk->cpl->kurikulum)->create();

        $this->put(route('admin.cpmk.update', $cpmk), [
            'cpl_id' => $cplLain->cpl_id,
            'kode_cpmk' => $cpmk->kode_cpmk,
            'deskripsi_cpmk' => $cpmk->deskripsi_cpmk,
        ])->assertSessionHasErrors(['cpl_id' => 'CPL tidak bisa diganti karena CPMK ini sudah dipetakan ke mata kuliah.']);
    }
}
