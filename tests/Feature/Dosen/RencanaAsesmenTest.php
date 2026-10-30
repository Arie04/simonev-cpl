<?php

namespace Tests\Feature\Dosen;

use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\MetodePenilaian;
use App\Models\NilaiAsesmen;
use App\Models\PemetaanCpmkMk;
use App\Models\PesertaKelas;
use App\Models\RencanaAsesmen;
use App\Models\Role;
use App\Models\SubCpmk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skenario uji 7 dan 8 (bagian 12 spesifikasi): dosen menambahkan asesmen pada kelas yang
 * diampu dan menghubungkannya dengan Sub-CPMK. Termasuk validasi bobot (bagian 6) dan
 * peringatan matriks bobot (bagian 7.6.7, Uji 5 di bagian 5.5).
 */
class RencanaAsesmenTest extends TestCase
{
    use RefreshDatabase;

    private KelasKuliah $kelas;

    private SubCpmk $subCpmk1;

    private SubCpmk $subCpmk2;

    private MetodePenilaian $uts;

    private User $dosen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kelas = KelasKuliah::factory()->create();
        $cpmk = Cpmk::factory()->for(Cpl::factory()->for($this->kelas->mataKuliah->kurikulum))->create();
        $pemetaan = PemetaanCpmkMk::factory()->for($cpmk)->for($this->kelas->mataKuliah)->create(['bobot_cpmk' => 60]);
        $this->subCpmk1 = SubCpmk::factory()->for($pemetaan)->create(['kode_sub_cpmk' => 'S1', 'bobot_sub_cpmk' => 25]);
        $this->subCpmk2 = SubCpmk::factory()->for($pemetaan)->create(['kode_sub_cpmk' => 'S2', 'bobot_sub_cpmk' => 35]);
        $this->uts = MetodePenilaian::factory()->create(['kode_metode' => 'UTS', 'nama_metode' => 'Ujian Tengah Semester']);

        $dosen = Dosen::factory()->create();
        $this->kelas->dosen()->attach($dosen);
        $this->dosen = $this->masukSebagai(Role::DOSEN, $dosen->user);
    }

    private function isian(array $ubah = []): array
    {
        return [
            'metode_penilaian_id' => $this->uts->metode_penilaian_id,
            'nama_asesmen' => 'UTS',
            'bobot_asesmen' => '30',
            'nilai_maksimum' => '100',
            'tanggal_asesmen' => '2026-10-26',
            'kontribusi' => [$this->subCpmk1->sub_cpmk_id => '15', $this->subCpmk2->sub_cpmk_id => '15'],
            ...$ubah,
        ];
    }

    public function test_dosen_menambahkan_asesmen_dan_memetakannya_ke_sub_cpmk(): void
    {
        $this->get(route('dosen.kelas.rencana-asesmen.create', $this->kelas))->assertOk()->assertSee('S1')->assertSee('S2');

        $this->post(route('dosen.kelas.rencana-asesmen.store', $this->kelas), $this->isian(['bobot_asesmen' => '30,5', 'kontribusi' => [
            $this->subCpmk1->sub_cpmk_id => '15,5',
            $this->subCpmk2->sub_cpmk_id => '15',
        ]]))->assertRedirect(route('dosen.kelas.rencana-asesmen.index', $this->kelas))
            ->assertSessionHas('sukses', 'Rencana asesmen berhasil ditambahkan.');

        $rencana = RencanaAsesmen::where('nama_asesmen', 'UTS')->firstOrFail();
        $this->assertSame('30.50', $rencana->bobot_asesmen);
        $this->assertEqualsCanonicalizing(
            ['S1' => '15.50', 'S2' => '15.00'],
            $rencana->subCpmk->mapWithKeys(fn ($subCpmk) => [$subCpmk->kode_sub_cpmk => $subCpmk->pivot->bobot_kontribusi])->all(),
        );

        $this->get(route('dosen.kelas.rencana-asesmen.index', $this->kelas))
            ->assertOk()
            ->assertSee('UTS')
            ->assertSee('S1 (15,5)')
            ->assertSee('Matriks Bobot Penilaian');
    }

    public function test_jumlah_kontribusi_harus_sama_dengan_bobot_asesmen(): void
    {
        $this->post(route('dosen.kelas.rencana-asesmen.store', $this->kelas), $this->isian(['bobot_asesmen' => '40']))
            ->assertSessionHasErrors(['kontribusi' => 'Jumlah bobot kontribusi (30) harus sama dengan bobot asesmen (40).']);

        $this->assertDatabaseCount('rencana_asesmen', 0);
    }

    public function test_asesmen_wajib_memetakan_minimal_satu_sub_cpmk_dan_nilai_maksimum_positif(): void
    {
        $this->post(route('dosen.kelas.rencana-asesmen.store', $this->kelas), $this->isian(['kontribusi' => [], 'nilai_maksimum' => '0']))
            ->assertSessionHasErrors([
                'kontribusi' => 'Pilih minimal satu Sub-CPMK yang diukur asesmen ini.',
                'nilai_maksimum' => 'Nilai maksimum harus lebih besar dari 0.',
            ]);
    }

    public function test_sub_cpmk_dari_mata_kuliah_lain_ditolak(): void
    {
        $subCpmkLain = SubCpmk::factory()->create();

        $this->post(route('dosen.kelas.rencana-asesmen.store', $this->kelas), $this->isian([
            'bobot_asesmen' => '10',
            'kontribusi' => [$subCpmkLain->sub_cpmk_id => '10'],
        ]))->assertSessionHasErrors(['kontribusi' => 'Sub-CPMK yang dipilih harus milik mata kuliah kelas ini.']);
    }

    public function test_dosen_tidak_bisa_membuka_kelas_yang_tidak_diampu(): void
    {
        $kelasLain = KelasKuliah::factory()->create();

        $this->get(route('dosen.kelas.rencana-asesmen.index', $kelasLain))->assertForbidden();
        $this->post(route('dosen.kelas.rencana-asesmen.store', $kelasLain), $this->isian())->assertForbidden();
    }

    public function test_dosen_mengubah_asesmen_dan_pemetaannya(): void
    {
        $rencana = RencanaAsesmen::factory()->for($this->kelas)->create(['nama_asesmen' => 'Kuis', 'bobot_asesmen' => 10]);
        $rencana->petakanSubCpmk([$this->subCpmk1->sub_cpmk_id => 10]);

        $this->put(route('dosen.kelas.rencana-asesmen.update', [$this->kelas, $rencana]), $this->isian([
            'nama_asesmen' => 'Kuis 1',
            'bobot_asesmen' => '20',
            'kontribusi' => [$this->subCpmk2->sub_cpmk_id => '20'],
        ]))->assertSessionHasNoErrors();

        $rencana->refresh();
        $this->assertSame('Kuis 1', $rencana->nama_asesmen);
        $this->assertSame(['S2'], $rencana->subCpmk->pluck('kode_sub_cpmk')->all());
    }

    public function test_nilai_maksimum_tidak_bisa_diturunkan_di_bawah_nilai_yang_sudah_ada(): void
    {
        $rencana = RencanaAsesmen::factory()->for($this->kelas)->create(['bobot_asesmen' => 30, 'nilai_maksimum' => 100]);
        NilaiAsesmen::factory()->for($rencana)->create([
            'peserta_kelas_id' => PesertaKelas::factory()->for($this->kelas),
            'nilai' => 85,
        ]);

        $this->put(route('dosen.kelas.rencana-asesmen.update', [$this->kelas, $rencana]), $this->isian(['nilai_maksimum' => '80']))
            ->assertSessionHasErrors(['nilai_maksimum' => 'Nilai maksimum tidak boleh lebih kecil dari nilai tertinggi yang sudah diinput (85).']);
    }

    public function test_asesmen_yang_sudah_punya_nilai_tidak_bisa_dihapus(): void
    {
        $nilai = NilaiAsesmen::factory()->for(RencanaAsesmen::factory()->for($this->kelas))->create();

        $this->delete(route('dosen.kelas.rencana-asesmen.destroy', [$this->kelas, $nilai->rencanaAsesmen]))
            ->assertSessionHas('gagal', 'Asesmen tidak bisa dihapus karena sudah punya nilai.');
    }

    public function test_asesmen_tanpa_nilai_bisa_dihapus_beserta_pemetaannya(): void
    {
        $rencana = RencanaAsesmen::factory()->for($this->kelas)->create();
        $rencana->petakanSubCpmk([$this->subCpmk1->sub_cpmk_id => 25]);

        $this->delete(route('dosen.kelas.rencana-asesmen.destroy', [$this->kelas, $rencana]))->assertSessionHas('sukses');

        $this->assertModelMissing($rencana);
        $this->assertDatabaseCount('rencana_asesmen_sub_cpmk', 0);
    }

    public function test_asesmen_kelas_lain_tidak_bisa_diubah_lewat_alamat_kelas_ini(): void
    {
        $rencanaKelasLain = RencanaAsesmen::factory()->create();

        $this->get(route('dosen.kelas.rencana-asesmen.edit', [$this->kelas, $rencanaKelasLain]))->assertNotFound();
    }

    public function test_halaman_menampilkan_peringatan_bobot_yang_tidak_sesuai_target(): void
    {
        $kuis = MetodePenilaian::factory()->create(['kode_metode' => 'KUIS', 'nama_metode' => 'Kuis']);
        $this->subCpmk1->metodePenilaian()->attach($this->uts);
        $rencana = RencanaAsesmen::factory()->for($this->kelas)->for($kuis)->create(['nama_asesmen' => 'Kuis 1', 'bobot_asesmen' => 10]);
        $rencana->petakanSubCpmk([$this->subCpmk1->sub_cpmk_id => 10]);

        $this->get(route('dosen.kelas.rencana-asesmen.index', $this->kelas))
            ->assertSee('Total bobot asesmen kelas ini 10%, seharusnya 100%.')
            ->assertSee('Bobot kontribusi ke S1 di kelas ini 10%, belum sama dengan targetnya 25%.')
            ->assertSee('Bobot kontribusi ke S2 di kelas ini 0%, belum sama dengan targetnya 35%.')
            ->assertSee('Metode Kuis pada asesmen &quot;Kuis 1&quot; tidak termasuk metode yang direncanakan untuk S1.', false);
    }

    public function test_revisi_target_kurikulum_memunculkan_peringatan_di_kelas_yang_sudah_sesuai(): void
    {
        // Uji 5 (bagian 5.5): kelas sudah sesuai target, lalu target Sub-CPMK direvisi.
        $subCpmk3 = SubCpmk::factory()->for($this->subCpmk1->pemetaanCpmkMk)->create(['kode_sub_cpmk' => 'S3', 'bobot_sub_cpmk' => 40]);
        foreach ([$this->subCpmk1, $this->subCpmk2, $subCpmk3] as $subCpmk) {
            $subCpmk->metodePenilaian()->attach($this->uts);
        }
        $rencana = RencanaAsesmen::factory()->for($this->kelas)->for($this->uts)->create(['bobot_asesmen' => 100]);
        $rencana->petakanSubCpmk([
            $this->subCpmk1->sub_cpmk_id => 25,
            $this->subCpmk2->sub_cpmk_id => 35,
            $subCpmk3->sub_cpmk_id => 40,
        ]);

        $this->get(route('dosen.kelas.rencana-asesmen.index', $this->kelas))->assertDontSee('Bobot kelas belum sesuai target');

        $this->subCpmk1->update(['bobot_sub_cpmk' => 30]);
        $this->subCpmk2->update(['bobot_sub_cpmk' => 30]);

        $this->get(route('dosen.kelas.rencana-asesmen.index', $this->kelas))
            ->assertSee('Bobot kelas belum sesuai target')
            ->assertSee('Bobot kontribusi ke S1 di kelas ini 25%, belum sama dengan targetnya 30%.');
    }

    public function test_peran_selain_dosen_tidak_bisa_membuka_rencana_asesmen(): void
    {
        $this->masukSebagai(Role::ADMIN);

        $this->get(route('dosen.kelas.rencana-asesmen.index', $this->kelas))->assertForbidden();
    }
}
