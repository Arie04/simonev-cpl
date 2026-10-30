<?php

namespace Tests\Feature\Admin;

use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use App\Models\MetodePenilaian;
use App\Models\PemetaanCpmkMk;
use App\Models\RencanaAsesmen;
use App\Models\Role;
use App\Models\SubCpmk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Data master Sub-CPMK beserta rincian penilaiannya, dan ringkasan bobot di detail mata kuliah
 * (bagian 7.6.4 spesifikasi, KD-35).
 */
class SubCpmkTest extends TestCase
{
    use RefreshDatabase;

    private Kurikulum $kurikulum;

    private PemetaanCpmkMk $pemetaan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kurikulum = Kurikulum::factory()->create(['tahun_berlaku' => 2026]);
        $this->pemetaan = PemetaanCpmkMk::factory()
            ->for(Cpmk::factory()->for(Cpl::factory()->for($this->kurikulum))->state(['kode_cpmk' => 'CPMK021']))
            ->for(MataKuliah::factory()->for($this->kurikulum)->state(['kode_mata_kuliah' => 'INF101']))
            ->create(['bobot_cpmk' => 40]);

        $this->masukSebagai(Role::ADMIN);
    }

    private function isianSubCpmk(array $ubah = []): array
    {
        return [
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'pemetaan_cpmk_mk_id' => $this->pemetaan->pemetaan_cpmk_mk_id,
            'kode_sub_cpmk' => 'Sub-CPMK0211',
            'deskripsi_sub_cpmk' => 'Mampu menentukan nilai kebenaran proposisi majemuk',
            'bobot_sub_cpmk' => '20',
            'materi' => 'Logika proposisi',
            'pertemuan_mulai' => 1,
            'pertemuan_selesai' => 3,
            ...$ubah,
        ];
    }

    public function test_admin_menambahkan_sub_cpmk_ke_pasangan_cpmk_mk_lalu_tampil(): void
    {
        $this->get(route('admin.sub-cpmk.create'))->assertOk()->assertSee('INF101');

        $this->post(route('admin.sub-cpmk.store'), $this->isianSubCpmk(['bobot_sub_cpmk' => '12,5']))
            ->assertRedirect(route('admin.sub-cpmk.index'));

        $this->assertDatabaseHas('sub_cpmk', [
            'pemetaan_cpmk_mk_id' => $this->pemetaan->pemetaan_cpmk_mk_id,
            'kode_sub_cpmk' => 'Sub-CPMK0211',
            'bobot_sub_cpmk' => 12.5,
        ]);
        $this->get(route('admin.sub-cpmk.index'))
            ->assertSee('Sub-CPMK0211')
            ->assertSee('CPMK021 · INF101')
            ->assertSee('12,5');
    }

    public function test_tambah_sub_cpmk_meminta_pemetaan_cpmk_mk_jika_belum_ada(): void
    {
        $this->pemetaan->delete();

        $this->get(route('admin.sub-cpmk.create'))
            ->assertRedirect(route('admin.pemetaan.edit', ['jenis' => 'cpmk-mk']))
            ->assertSessionHas('gagal', 'Buat pemetaan CPMK–Mata Kuliah terlebih dahulu.');
    }

    public function test_kode_sub_cpmk_unik_per_pasangan_tetapi_boleh_sama_di_pasangan_lain(): void
    {
        SubCpmk::factory()->for($this->pemetaan)->create(['kode_sub_cpmk' => 'Sub-CPMK0211']);
        $pasanganLain = PemetaanCpmkMk::factory()
            ->for($this->pemetaan->cpmk)
            ->for(MataKuliah::factory()->for($this->kurikulum))
            ->create();

        $this->post(route('admin.sub-cpmk.store'), $this->isianSubCpmk())
            ->assertSessionHasErrors(['kode_sub_cpmk' => 'Kode Sub-CPMK sudah dipakai di pasangan CPMK–MK ini.']);

        $this->post(route('admin.sub-cpmk.store'), $this->isianSubCpmk(['pemetaan_cpmk_mk_id' => $pasanganLain->pemetaan_cpmk_mk_id]))
            ->assertSessionHasNoErrors();
    }

    public function test_pasangan_dari_kurikulum_lain_dan_bobot_di_luar_batas_ditolak(): void
    {
        $pasanganKurikulumLain = PemetaanCpmkMk::factory()->create();

        $this->post(route('admin.sub-cpmk.store'), $this->isianSubCpmk([
            'pemetaan_cpmk_mk_id' => $pasanganKurikulumLain->pemetaan_cpmk_mk_id,
            'bobot_sub_cpmk' => '101',
            'pertemuan_mulai' => 5,
            'pertemuan_selesai' => 2,
        ]))->assertSessionHasErrors([
            'pemetaan_cpmk_mk_id' => 'Pasangan CPMK–MK yang dipilih tidak valid.',
            'bobot_sub_cpmk' => 'Bobot Sub-CPMK harus bernilai 0 sampai 100.',
            'pertemuan_selesai' => 'Pertemuan selesai tidak boleh sebelum pertemuan mulai.',
        ]);
    }

    public function test_sub_cpmk_yang_dipakai_rencana_asesmen_tidak_bisa_dihapus_atau_dipindah(): void
    {
        $subCpmk = SubCpmk::factory()->for($this->pemetaan)->create();
        RencanaAsesmen::factory()->create()->subCpmk()->attach($subCpmk, ['bobot_kontribusi' => 10]);
        $pasanganLain = PemetaanCpmkMk::factory()->for($this->pemetaan->cpmk)->for(MataKuliah::factory()->for($this->kurikulum))->create();

        $this->delete(route('admin.sub-cpmk.destroy', $subCpmk))
            ->assertSessionHas('gagal', 'Sub-CPMK tidak bisa dihapus karena sudah dipakai rencana asesmen.');

        $this->put(route('admin.sub-cpmk.update', $subCpmk), $this->isianSubCpmk(['pemetaan_cpmk_mk_id' => $pasanganLain->pemetaan_cpmk_mk_id]))
            ->assertSessionHasErrors(['pemetaan_cpmk_mk_id' => 'Pasangan CPMK–MK tidak bisa diganti karena Sub-CPMK ini sudah dipakai rencana asesmen.']);

        $this->assertModelExists($subCpmk);
    }

    public function test_menghapus_sub_cpmk_ikut_menghapus_rincian_penilaiannya(): void
    {
        $subCpmk = SubCpmk::factory()->for($this->pemetaan)->create();
        $subCpmk->metodePenilaian()->attach(MetodePenilaian::factory()->create());

        $this->delete(route('admin.sub-cpmk.destroy', $subCpmk))->assertSessionHas('sukses');

        $this->assertModelMissing($subCpmk);
        $this->assertDatabaseCount('sub_cpmk_penilaian', 0);
    }

    public function test_admin_mengelola_rincian_penilaian_di_detail_sub_cpmk(): void
    {
        $subCpmk = SubCpmk::factory()->for($this->pemetaan)->create();
        $uts = MetodePenilaian::factory()->create(['kode_metode' => 'UTS', 'nama_metode' => 'Ujian Tengah Semester']);
        $kuis = MetodePenilaian::factory()->create(['kode_metode' => 'KUIS', 'nama_metode' => 'Kuis']);

        $this->post(route('admin.sub-cpmk.penilaian.store', $subCpmk), [
            'metode_penilaian_id' => $uts->metode_penilaian_id,
            'teknik_penilaian' => 'Tes tertulis',
            'instrumen' => 'Soal uraian',
            'kriteria' => 'Ketepatan jawaban',
        ])->assertRedirect(route('admin.sub-cpmk.show', $subCpmk));

        $this->get(route('admin.sub-cpmk.show', $subCpmk))
            ->assertOk()
            ->assertSee('Ujian Tengah Semester')
            ->assertSee('Soal uraian');

        // Satu metode cukup sekali per Sub-CPMK.
        $this->post(route('admin.sub-cpmk.penilaian.store', $subCpmk), ['metode_penilaian_id' => $uts->metode_penilaian_id])
            ->assertSessionHasErrors(['metode_penilaian_id' => 'Metode ini sudah ada di rincian penilaian Sub-CPMK ini.']);

        $penilaian = $subCpmk->penilaian()->first();
        $this->put(route('admin.sub-cpmk.penilaian.update', [$subCpmk, $penilaian]), ['metode_penilaian_id' => $kuis->metode_penilaian_id])
            ->assertSessionHasNoErrors();
        $this->assertSame($kuis->metode_penilaian_id, $penilaian->fresh()->metode_penilaian_id);

        $this->delete(route('admin.sub-cpmk.penilaian.destroy', [$subCpmk, $penilaian]))->assertSessionHas('sukses');
        $this->assertDatabaseCount('sub_cpmk_penilaian', 0);
    }

    public function test_rincian_penilaian_harus_milik_sub_cpmk_di_alamatnya(): void
    {
        $subCpmk = SubCpmk::factory()->for($this->pemetaan)->create();
        $subCpmkLain = SubCpmk::factory()->create();
        $subCpmkLain->metodePenilaian()->attach(MetodePenilaian::factory()->create());

        $this->get(route('admin.sub-cpmk.penilaian.edit', [$subCpmk, $subCpmkLain->penilaian()->first()]))->assertNotFound();
    }

    public function test_detail_mata_kuliah_menampilkan_ringkasan_bobot_dan_peringatan(): void
    {
        SubCpmk::factory()->for($this->pemetaan)->create(['kode_sub_cpmk' => 'Sub-CPMK0211', 'bobot_sub_cpmk' => 20]);
        SubCpmk::factory()->for($this->pemetaan)->create(['kode_sub_cpmk' => 'Sub-CPMK0212', 'bobot_sub_cpmk' => 15]);

        $this->get(route('admin.mata-kuliah.show', $this->pemetaan->mataKuliah))
            ->assertOk()
            ->assertSee('Ringkasan Bobot CPMK dan Sub-CPMK')
            ->assertSeeInOrder(['CPMK021', 'Sub-CPMK0211', 'Sub-CPMK0212'])
            ->assertSee('Jumlah bobot Sub-CPMK CPMK021 adalah 35, belum sama dengan bobot CPMK 40.')
            ->assertSee('Total bobot CPMK mata kuliah ini 40, seharusnya 100.');
    }
}
