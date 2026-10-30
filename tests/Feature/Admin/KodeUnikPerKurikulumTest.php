<?php

namespace Tests\Feature\Admin;

use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Kurikulum;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Kode PL, CPL, BK, MK, dan CPMK tidak boleh ganda dalam satu kurikulum, tetapi boleh sama
 * di kurikulum lain (bagian 6 spesifikasi, Uji 6 di bagian 5.5).
 */
class KodeUnikPerKurikulumTest extends TestCase
{
    use RefreshDatabase;

    public static function kodeKurikulum(): array
    {
        return [
            'kode PL' => ['profil-lulusan', 'kode_pl', 'PL01', ['deskripsi_pl' => 'Pengembang perangkat lunak'], 'Kode PL sudah dipakai di kurikulum ini.'],
            'kode CPL' => ['cpl', 'kode_cpl', 'CPL01', ['kategori_cpl' => 'Sikap', 'deskripsi_cpl' => 'Bertakwa'], 'Kode CPL sudah dipakai di kurikulum ini.'],
            'kode BK' => ['bahan-kajian', 'kode_bk', 'BK01', ['nama_bahan_kajian' => 'Algoritma'], 'Kode BK sudah dipakai di kurikulum ini.'],
            'kode MK' => ['mata-kuliah', 'kode_mata_kuliah', 'INF202', [
                'nama_mata_kuliah' => 'Basis Data', 'semester' => 3, 'jumlah_sks' => 3, 'jenis_mata_kuliah' => 'Wajib',
            ], 'Kode mata kuliah sudah dipakai di kurikulum ini.'],
        ];
    }

    #[DataProvider('kodeKurikulum')]
    public function test_kode_ganda_dalam_satu_kurikulum_ditolak_tetapi_boleh_di_kurikulum_lain(
        string $route,
        string $kolomKode,
        string $kode,
        array $isianLain,
        string $pesanGanda,
    ): void {
        $kurikulum2021 = Kurikulum::factory()->create(['tahun_berlaku' => 2021]);
        $kurikulum2026 = Kurikulum::factory()->create(['tahun_berlaku' => 2026]);
        $this->masukSebagai(Role::ADMIN);

        $this->post(route("admin.{$route}.store"), [...$isianLain, 'kurikulum_id' => $kurikulum2021->kurikulum_id, $kolomKode => $kode])
            ->assertSessionHasNoErrors();

        // Kode yang sama di kurikulum lain boleh.
        $this->post(route("admin.{$route}.store"), [...$isianLain, 'kurikulum_id' => $kurikulum2026->kurikulum_id, $kolomKode => $kode])
            ->assertSessionHasNoErrors();

        // Kode yang sama di kurikulum yang sama ditolak, tanpa membedakan huruf besar/kecil.
        $this->post(route("admin.{$route}.store"), [...$isianLain, 'kurikulum_id' => $kurikulum2026->kurikulum_id, $kolomKode => strtolower($kode)])
            ->assertSessionHasErrors([$kolomKode => $pesanGanda]);
    }

    public function test_kode_cpmk_unik_dalam_satu_kurikulum_walaupun_cpl_nya_berbeda(): void
    {
        $kurikulum = Kurikulum::factory()->create();
        [$cpl01, $cpl02] = Cpl::factory()->count(2)->for($kurikulum)->create();
        Cpmk::factory()->for($cpl01)->create(['kode_cpmk' => 'CPMK011']);
        $cplKurikulumLain = Cpl::factory()->create();
        $this->masukSebagai(Role::ADMIN);

        $this->post(route('admin.cpmk.store'), [
            'kurikulum_id' => $kurikulum->kurikulum_id,
            'cpl_id' => $cpl02->cpl_id,
            'kode_cpmk' => 'CPMK011',
            'deskripsi_cpmk' => 'Deskripsi',
        ])->assertSessionHasErrors(['kode_cpmk' => 'Kode CPMK sudah dipakai di kurikulum ini.']);

        $this->post(route('admin.cpmk.store'), [
            'kurikulum_id' => $cplKurikulumLain->kurikulum_id,
            'cpl_id' => $cplKurikulumLain->cpl_id,
            'kode_cpmk' => 'CPMK011',
            'deskripsi_cpmk' => 'Deskripsi',
        ])->assertSessionHasNoErrors();
    }

    public function test_mengubah_data_tanpa_mengganti_kode_tidak_dianggap_ganda(): void
    {
        $cpl = Cpl::factory()->create(['kode_cpl' => 'CPL01']);
        $this->masukSebagai(Role::ADMIN);

        $this->put(route('admin.cpl.update', $cpl), [
            'kode_cpl' => 'CPL01',
            'kategori_cpl' => 'Sikap',
            'deskripsi_cpl' => 'Deskripsi diperbarui',
        ])->assertSessionHasNoErrors();
    }
}
