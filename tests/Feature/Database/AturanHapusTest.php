<?php

namespace Tests\Feature\Database;

use App\Models\BahanKajian;
use App\Models\Cpl;
use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use App\Models\NilaiAsesmen;
use App\Models\PesertaKelas;
use App\Models\ProfilLulusan;
use App\Models\RencanaAsesmen;
use App\Models\Role;
use App\Models\SubCpmk;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aturan cascade/restrict di tingkat database (KD-20) dan kode unik per
 * kurikulum (KD-07). Mulai iterasi 2, aplikasi juga memeriksa aturan ini
 * dan menampilkan pesan sebelum database menolak.
 */
class AturanHapusTest extends TestCase
{
    use RefreshDatabase;

    public function test_menghapus_akun_ikut_menghapus_baris_user_roles(): void
    {
        $user = User::factory()->denganPeran(Role::ADMIN)->create();

        $user->delete();

        $this->assertDatabaseCount('user_roles', 0);
        $this->assertDatabaseCount('roles', 1);
    }

    public function test_peran_yang_masih_dipakai_akun_tidak_bisa_dihapus(): void
    {
        User::factory()->denganPeran(Role::ADMIN)->create();

        $this->expectException(QueryException::class);

        Role::where('kode_role', Role::ADMIN)->first()->delete();
    }

    public function test_menghapus_cpl_ikut_menghapus_pemetaannya_ke_profil_lulusan_dan_bahan_kajian(): void
    {
        $cpl = Cpl::factory()->create();
        $cpl->profilLulusan()->attach(ProfilLulusan::factory()->create(['kurikulum_id' => $cpl->kurikulum_id]));
        $cpl->bahanKajian()->attach(BahanKajian::factory()->create(['kurikulum_id' => $cpl->kurikulum_id]));

        $cpl->delete();

        $this->assertDatabaseCount('pemetaan_cpl_pl', 0);
        $this->assertDatabaseCount('pemetaan_cpl_bk', 0);
        $this->assertDatabaseCount('profil_lulusan', 1);
        $this->assertDatabaseCount('bahan_kajian', 1);
    }

    public function test_cpmk_yang_pemetaannya_sudah_punya_sub_cpmk_tidak_bisa_dihapus(): void
    {
        $subCpmk = SubCpmk::factory()->create();

        $this->expectException(QueryException::class);

        $subCpmk->pemetaanCpmkMk->cpmk->delete();
    }

    public function test_menghapus_rencana_asesmen_ikut_menghapus_pemetaan_sub_cpmk_nya(): void
    {
        $rencanaAsesmen = RencanaAsesmen::factory()->create();
        $rencanaAsesmen->subCpmk()->attach(SubCpmk::factory()->create(), ['bobot_kontribusi' => 10]);

        $rencanaAsesmen->delete();

        $this->assertDatabaseCount('rencana_asesmen_sub_cpmk', 0);
        $this->assertDatabaseCount('sub_cpmk', 1);
    }

    public function test_sub_cpmk_yang_dipakai_rencana_asesmen_tidak_bisa_dihapus(): void
    {
        $subCpmk = SubCpmk::factory()->create();
        RencanaAsesmen::factory()->create()->subCpmk()->attach($subCpmk, ['bobot_kontribusi' => 10]);

        $this->expectException(QueryException::class);

        $subCpmk->delete();
    }

    public function test_menghapus_kelas_ikut_menghapus_dosen_pengampu_dan_peserta_yang_belum_bernilai(): void
    {
        $kelasKuliah = KelasKuliah::factory()->create();
        $kelasKuliah->dosen()->attach(Dosen::factory()->create());
        $kelasKuliah->mahasiswa()->attach(Mahasiswa::factory()->create());

        $kelasKuliah->delete();

        $this->assertDatabaseCount('dosen_kelas', 0);
        $this->assertDatabaseCount('peserta_kelas', 0);
        $this->assertDatabaseCount('dosen', 1);
        $this->assertDatabaseCount('mahasiswa', 1);
    }

    public function test_dosen_yang_masih_mengampu_kelas_tidak_bisa_dihapus(): void
    {
        $dosen = Dosen::factory()->create();
        KelasKuliah::factory()->create()->dosen()->attach($dosen);

        $this->expectException(QueryException::class);

        $dosen->delete();
    }

    public function test_mahasiswa_yang_masih_terdaftar_di_kelas_tidak_bisa_dihapus(): void
    {
        $pesertaKelas = PesertaKelas::factory()->create();

        $this->expectException(QueryException::class);

        $pesertaKelas->mahasiswa->delete();
    }

    public function test_peserta_yang_sudah_punya_nilai_tidak_bisa_dihapus(): void
    {
        $nilaiAsesmen = NilaiAsesmen::factory()->create();

        $this->expectException(QueryException::class);

        $nilaiAsesmen->pesertaKelas->delete();
    }

    public function test_kode_cpl_yang_sama_boleh_dipakai_di_kurikulum_berbeda(): void
    {
        Cpl::factory()->create(['kode_cpl' => 'CPL01']);
        Cpl::factory()->create(['kode_cpl' => 'CPL01']);

        $this->assertDatabaseCount('cpl', 2);
        $this->assertDatabaseCount('kurikulum', 2);
    }

    public function test_kode_cpl_ganda_dalam_satu_kurikulum_ditolak_tanpa_membedakan_huruf_besar_kecil(): void
    {
        $cpl = Cpl::factory()->create(['kode_cpl' => 'CPL01']);

        $this->expectException(UniqueConstraintViolationException::class);

        Cpl::factory()->create(['kurikulum_id' => $cpl->kurikulum_id, 'kode_cpl' => 'cpl01']);
    }
}
