<?php

namespace Tests\Feature\Admin;

use App\Models\BahanKajian;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use App\Models\PemetaanCpmkMk;
use App\Models\ProfilLulusan;
use App\Models\Role;
use App\Models\SubCpmk;
use App\Services\MatriksPemetaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skenario uji 5 (bagian 12 spesifikasi): Admin menyimpan hubungan antarkomponen kurikulum
 * lewat matriks pemetaan, lalu pemetaannya tersimpan.
 */
class PemetaanKurikulumTest extends TestCase
{
    use RefreshDatabase;

    private Kurikulum $kurikulum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kurikulum = Kurikulum::factory()->create(['tahun_berlaku' => 2026]);
        $this->masukSebagai(Role::ADMIN);
    }

    private function kunci(string $idBaris, string $idKolom): string
    {
        return MatriksPemetaan::kunci($idBaris, $idKolom);
    }

    public function test_admin_menyimpan_pemetaan_cpl_ke_profil_lulusan(): void
    {
        $cpl = Cpl::factory()->for($this->kurikulum)->create(['kode_cpl' => 'CPL01']);
        [$pl01, $pl02] = ProfilLulusan::factory()->count(2)->for($this->kurikulum)->sequence(['kode_pl' => 'PL01'], ['kode_pl' => 'PL02'])->create();

        $this->get(route('admin.pemetaan.edit', ['jenis' => 'cpl-pl']))
            ->assertOk()
            ->assertSee('Matriks CPL – Profil Lulusan')
            ->assertSee('CPL01')
            ->assertSee('PL02');

        $this->put(route('admin.pemetaan.update'), [
            'jenis' => 'cpl-pl',
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'terhubung' => [$this->kunci($cpl->cpl_id, $pl01->profil_lulusan_id), $this->kunci($cpl->cpl_id, $pl02->profil_lulusan_id)],
        ])->assertRedirect(route('admin.pemetaan.edit', ['jenis' => 'cpl-pl', 'kurikulum' => $this->kurikulum->kurikulum_id]))
            ->assertSessionHas('sukses', 'Pemetaan CPL – Profil Lulusan berhasil disimpan.');

        $this->assertEqualsCanonicalizing(['PL01', 'PL02'], $cpl->profilLulusan()->pluck('kode_pl')->all());

        // Lingkaran yang tidak dicentang lagi berarti hubungannya dilepas.
        $this->put(route('admin.pemetaan.update'), [
            'jenis' => 'cpl-pl',
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'terhubung' => [$this->kunci($cpl->cpl_id, $pl02->profil_lulusan_id)],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['PL02'], $cpl->profilLulusan()->pluck('kode_pl')->all());
    }

    public function test_admin_menyimpan_pemetaan_cpl_ke_bahan_kajian(): void
    {
        $cpl = Cpl::factory()->for($this->kurikulum)->create();
        $bahanKajian = BahanKajian::factory()->for($this->kurikulum)->create();

        $this->put(route('admin.pemetaan.update'), [
            'jenis' => 'cpl-bk',
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'terhubung' => [$this->kunci($cpl->cpl_id, $bahanKajian->bahan_kajian_id)],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pemetaan_cpl_bk', ['cpl_id' => $cpl->cpl_id, 'bahan_kajian_id' => $bahanKajian->bahan_kajian_id]);
    }

    public function test_pemetaan_bahan_kajian_ke_mata_kuliah_menyimpan_bobot_dengan_koma_desimal(): void
    {
        $bahanKajian = BahanKajian::factory()->for($this->kurikulum)->create();
        $mataKuliah = MataKuliah::factory()->for($this->kurikulum)->create();
        $kunci = $this->kunci($bahanKajian->bahan_kajian_id, $mataKuliah->mata_kuliah_id);

        $this->put(route('admin.pemetaan.update'), [
            'jenis' => 'bk-mk',
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'terhubung' => [$kunci],
            'bobot' => [$kunci => '12,5'],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pemetaan_bk_mk', ['bahan_kajian_id' => $bahanKajian->bahan_kajian_id, 'bobot_bk_mk' => 12.5]);
    }

    public function test_pemetaan_cpmk_ke_mata_kuliah_menyimpan_dan_memperbarui_bobot(): void
    {
        $cpmk = Cpmk::factory()->for(Cpl::factory()->for($this->kurikulum))->create();
        $mataKuliah = MataKuliah::factory()->for($this->kurikulum)->create();
        $kunci = $this->kunci($cpmk->cpmk_id, $mataKuliah->mata_kuliah_id);
        $isian = ['jenis' => 'cpmk-mk', 'kurikulum_id' => $this->kurikulum->kurikulum_id, 'terhubung' => [$kunci]];

        $this->put(route('admin.pemetaan.update'), [...$isian, 'bobot' => [$kunci => '60']])->assertSessionHasNoErrors();
        $this->put(route('admin.pemetaan.update'), [...$isian, 'bobot' => [$kunci => '100']])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('pemetaan_cpmk_mk', 1);
        $this->assertDatabaseHas('pemetaan_cpmk_mk', ['cpmk_id' => $cpmk->cpmk_id, 'mata_kuliah_id' => $mataKuliah->mata_kuliah_id, 'bobot_cpmk' => 100]);
    }

    public function test_bobot_pasangan_terpilih_wajib_0_sampai_100(): void
    {
        $cpmk = Cpmk::factory()->for(Cpl::factory()->for($this->kurikulum))->create();
        $mataKuliah = MataKuliah::factory()->for($this->kurikulum)->create();
        $kunci = $this->kunci($cpmk->cpmk_id, $mataKuliah->mata_kuliah_id);

        $this->put(route('admin.pemetaan.update'), [
            'jenis' => 'cpmk-mk',
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'terhubung' => [$kunci],
            'bobot' => [$kunci => '120'],
        ])->assertSessionHasErrors(["bobot.{$kunci}" => 'Bobot harus berupa angka 0 sampai 100.']);

        $this->assertDatabaseCount('pemetaan_cpmk_mk', 0);
    }

    public function test_pemetaan_hanya_boleh_menghubungkan_data_dari_kurikulum_yang_sama(): void
    {
        $cpl = Cpl::factory()->for($this->kurikulum)->create();
        $profilLulusanKurikulumLain = ProfilLulusan::factory()->create();

        $this->put(route('admin.pemetaan.update'), [
            'jenis' => 'cpl-pl',
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'terhubung' => [$this->kunci($cpl->cpl_id, $profilLulusanKurikulumLain->profil_lulusan_id)],
        ])->assertSessionHasErrors(['terhubung' => 'Pemetaan hanya boleh menghubungkan data dari kurikulum yang sama.']);

        $this->assertDatabaseCount('pemetaan_cpl_pl', 0);
    }

    public function test_pasangan_cpmk_mk_yang_sudah_punya_sub_cpmk_tidak_bisa_dilepas(): void
    {
        $subCpmk = SubCpmk::factory()->create();
        $pemetaan = $subCpmk->pemetaanCpmkMk;
        $kurikulum = $pemetaan->mataKuliah->kurikulum;

        $this->put(route('admin.pemetaan.update'), [
            'jenis' => 'cpmk-mk',
            'kurikulum_id' => $kurikulum->kurikulum_id,
            'terhubung' => [],
        ])->assertSessionHasErrors(['terhubung' => "Pemetaan {$pemetaan->label()} tidak bisa dilepas karena sudah memiliki Sub-CPMK."]);

        $this->assertModelExists($pemetaan);
    }

    public function test_matriks_cpmk_mk_memberi_peringatan_jika_total_bobot_mata_kuliah_bukan_100(): void
    {
        $mataKuliah = MataKuliah::factory()->for($this->kurikulum)->create(['kode_mata_kuliah' => 'INF101']);
        PemetaanCpmkMk::factory()
            ->for(Cpmk::factory()->for(Cpl::factory()->for($this->kurikulum)))
            ->for($mataKuliah)
            ->create(['bobot_cpmk' => 60]);

        $this->get(route('admin.pemetaan.edit', ['jenis' => 'cpmk-mk']))
            ->assertOk()
            ->assertSee('Total bobot (target 100)')
            ->assertSee('Total bobot CPMK mata kuliah ini belum 100');
    }

    public function test_peran_selain_admin_tidak_bisa_menyimpan_pemetaan(): void
    {
        $this->masukSebagai(Role::KOPRODI);

        $this->get(route('admin.pemetaan.edit'))->assertForbidden();
        $this->put(route('admin.pemetaan.update'), ['jenis' => 'cpl-pl'])->assertForbidden();
    }
}
