<?php

namespace Tests\Feature\Database;

use App\Models\BahanKajian;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\MetodePenilaian;
use App\Models\Profesi;
use App\Models\ProfilLulusan;
use App\Models\RencanaAsesmen;
use App\Models\Role;
use App\Models\SubCpmk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Semua PK memakai UUID, termasuk tabel pivot yang barisnya dibuat lewat
 * relasi belongsToMany (bagian 4 spesifikasi).
 */
class UuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_key_model_berupa_uuid_versi_7_yang_terurut_waktu(): void
    {
        $kurikulum = Kurikulum::factory()->create();

        $this->assertTrue(Str::isUuid($kurikulum->kurikulum_id, 7));
    }

    public function test_pivot_user_roles_terisi_uuid_saat_peran_ditambahkan_lewat_relasi(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['kode_role' => Role::DOSEN, 'nama_role' => 'Dosen']);

        $user->roles()->attach($role);

        $this->assertPivotBerUuid('user_roles', 'user_role_id');
    }

    public function test_pivot_profil_lulusan_profesi_terisi_uuid_saat_sync(): void
    {
        $profilLulusan = ProfilLulusan::factory()->create();
        $profesi = Profesi::factory()->create(['kurikulum_id' => $profilLulusan->kurikulum_id]);

        $profilLulusan->profesi()->sync([$profesi->profesi_id]);

        $this->assertPivotBerUuid('profil_lulusan_profesi', 'profil_lulusan_profesi_id');
    }

    public function test_pivot_pemetaan_cpl_pl_terisi_uuid_saat_sync(): void
    {
        $cpl = Cpl::factory()->create();
        $profilLulusan = ProfilLulusan::factory()->create(['kurikulum_id' => $cpl->kurikulum_id]);

        $cpl->profilLulusan()->sync([$profilLulusan->profil_lulusan_id]);

        $this->assertPivotBerUuid('pemetaan_cpl_pl', 'pemetaan_cpl_pl_id');
    }

    public function test_pivot_pemetaan_cpl_bk_terisi_uuid_saat_dibuat_lewat_relasi(): void
    {
        $cpl = Cpl::factory()->create();
        $bahanKajian = BahanKajian::factory()->create(['kurikulum_id' => $cpl->kurikulum_id]);

        $cpl->bahanKajian()->attach($bahanKajian);

        $this->assertPivotBerUuid('pemetaan_cpl_bk', 'pemetaan_cpl_bk_id');
    }

    public function test_pivot_pemetaan_bk_mk_terisi_uuid_saat_dibuat_lewat_relasi(): void
    {
        $mataKuliah = MataKuliah::factory()->create();
        $bahanKajian = BahanKajian::factory()->create(['kurikulum_id' => $mataKuliah->kurikulum_id]);

        $mataKuliah->bahanKajian()->attach($bahanKajian, ['bobot_bk_mk' => 10]);

        $this->assertPivotBerUuid('pemetaan_bk_mk', 'pemetaan_bk_mk_id');
    }

    public function test_pivot_pemetaan_cpmk_mk_terisi_uuid_saat_dibuat_lewat_relasi(): void
    {
        $cpmk = Cpmk::factory()->create();
        $mataKuliah = MataKuliah::factory()->create(['kurikulum_id' => $cpmk->cpl->kurikulum_id]);

        $cpmk->mataKuliah()->attach($mataKuliah, ['bobot_cpmk' => 60]);

        $this->assertPivotBerUuid('pemetaan_cpmk_mk', 'pemetaan_cpmk_mk_id');
    }

    public function test_pivot_sub_cpmk_penilaian_terisi_uuid_saat_dibuat_lewat_relasi(): void
    {
        $subCpmk = SubCpmk::factory()->create();

        $subCpmk->metodePenilaian()->attach(MetodePenilaian::factory()->create(), ['teknik_penilaian' => 'Tes tertulis']);

        $this->assertPivotBerUuid('sub_cpmk_penilaian', 'sub_cpmk_penilaian_id');
    }

    public function test_pivot_dosen_kelas_terisi_uuid_saat_dibuat_lewat_relasi(): void
    {
        $kelasKuliah = KelasKuliah::factory()->create();

        $kelasKuliah->dosen()->attach(Dosen::factory()->create());

        $this->assertPivotBerUuid('dosen_kelas', 'dosen_kelas_id');
    }

    public function test_pivot_peserta_kelas_terisi_uuid_saat_dibuat_lewat_relasi(): void
    {
        $kelasKuliah = KelasKuliah::factory()->create();

        $kelasKuliah->mahasiswa()->attach(Mahasiswa::factory()->create());

        $this->assertPivotBerUuid('peserta_kelas', 'peserta_kelas_id');
    }

    public function test_pivot_rencana_asesmen_sub_cpmk_terisi_uuid_saat_dibuat_lewat_relasi(): void
    {
        $rencanaAsesmen = RencanaAsesmen::factory()->create();

        $rencanaAsesmen->subCpmk()->attach(SubCpmk::factory()->create(), ['bobot_kontribusi' => 10]);

        $this->assertPivotBerUuid('rencana_asesmen_sub_cpmk', 'rencana_asesmen_sub_cpmk_id');
    }

    private function assertPivotBerUuid(string $tabel, string $kolomId): void
    {
        $id = DB::table($tabel)->value($kolomId);

        $this->assertNotNull($id, "Tabel {$tabel} tidak berisi baris.");
        $this->assertTrue(Str::isUuid($id, 7), "PK {$tabel} bukan UUID v7: {$id}");
    }
}
