<?php

namespace Tests\Feature\Admin;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use App\Models\PesertaKelas;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Data Dosen dan Mahasiswa sekaligus membuat akun login (bagian 7.6.4 spesifikasi, KD-36).
 */
class DosenMahasiswaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masukSebagai(Role::ADMIN);
    }

    public function test_menambah_dosen_membuat_akun_berperan_dosen_yang_bisa_login_dengan_nidn(): void
    {
        $this->post(route('admin.dosen.store'), [
            'nama' => 'Dr. Rahmat Hidayat',
            'nidn' => '0012345601',
            'email' => '',
            'password' => 'rahasia123',
        ])->assertRedirect(route('admin.dosen.index'));

        $dosen = Dosen::where('nidn', '0012345601')->firstOrFail();
        $this->assertSame('Dr. Rahmat Hidayat', $dosen->user->nama);
        $this->assertNull($dosen->user->email);
        $this->assertTrue($dosen->user->punyaPeran(Role::DOSEN));
        $this->assertTrue(Hash::check('rahasia123', $dosen->user->password));

        $this->get(route('admin.dosen.index'))->assertSee('Dr. Rahmat Hidayat')->assertSee('0012345601');

        auth()->logout();
        $this->post(route('login.store'), ['identitas' => '0012345601', 'password' => 'rahasia123'])
            ->assertRedirect(route('peran-aktif.create'));
    }

    public function test_menambah_mahasiswa_membuat_akun_berperan_mahasiswa(): void
    {
        $this->post(route('admin.mahasiswa.store'), [
            'nama' => 'Siti Aminah',
            'nim' => '07352311001',
            'angkatan' => 2023,
            'password' => 'rahasia123',
        ])->assertRedirect(route('admin.mahasiswa.index'));

        $mahasiswa = Mahasiswa::where('nim', '07352311001')->firstOrFail();
        $this->assertSame(2023, $mahasiswa->angkatan);
        $this->assertTrue($mahasiswa->user->punyaPeran(Role::MAHASISWA));

        $this->get(route('admin.mahasiswa.index'))
            ->assertSee('NPM')
            ->assertSee('Siti Aminah')
            ->assertSee('07352311001');
    }

    public function test_nidn_npm_dan_email_tidak_boleh_ganda(): void
    {
        $dosen = Dosen::factory()->create(['nidn' => '0012345601']);
        Mahasiswa::factory()->create(['nim' => '07352311001']);

        $this->post(route('admin.dosen.store'), [
            'nama' => 'Dosen Lain',
            'nidn' => '0012345601',
            'email' => $dosen->user->email,
            'password' => 'rahasia123',
        ])->assertSessionHasErrors(['nidn' => 'NIDN sudah dipakai.', 'email' => 'Email sudah dipakai.']);

        $this->post(route('admin.mahasiswa.store'), [
            'nama' => 'Mahasiswa Lain',
            'nim' => '07352311001',
            'angkatan' => 2023,
            'password' => 'rahasia123',
        ])->assertSessionHasErrors(['nim' => 'NPM sudah dipakai.']);
    }

    public function test_password_awal_minimal_8_karakter(): void
    {
        $this->post(route('admin.dosen.store'), [
            'nama' => 'Dosen Baru',
            'nidn' => '0012345699',
            'password' => 'pendek',
        ])->assertSessionHasErrors(['password' => 'Password awal minimal berisi 8 karakter.']);
    }

    public function test_mengubah_nama_dosen_ikut_mengubah_nama_akun(): void
    {
        $dosen = Dosen::factory()->create();

        $this->put(route('admin.dosen.update', $dosen), [
            'nama' => 'Nama Baru, M.Kom.',
            'nidn' => $dosen->nidn,
            'email' => 'baru@unkhair.ac.id',
        ])->assertRedirect(route('admin.dosen.index'));

        $dosen->refresh();
        $this->assertSame('Nama Baru, M.Kom.', $dosen->nama);
        $this->assertSame('Nama Baru, M.Kom.', $dosen->user->nama);
        $this->assertSame('baru@unkhair.ac.id', $dosen->user->email);
    }

    public function test_menghapus_dosen_ikut_menghapus_akunnya(): void
    {
        $dosen = Dosen::factory()->create();
        $user = $dosen->user;

        $this->delete(route('admin.dosen.destroy', $dosen))->assertSessionHas('sukses', 'Data dosen berhasil dihapus.');

        $this->assertModelMissing($dosen);
        $this->assertModelMissing($user);
    }

    public function test_menghapus_dosen_yang_juga_koprodi_hanya_mencabut_peran_dosen(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI, Role::DOSEN)->create();
        $dosen = Dosen::factory()->for($user)->create();

        $this->delete(route('admin.dosen.destroy', $dosen))->assertSessionHas('sukses');

        $this->assertModelMissing($dosen);
        $this->assertModelExists($user);
        $this->assertSame([Role::KOPRODI], $user->roles()->pluck('kode_role')->all());
    }

    public function test_dosen_yang_masih_mengampu_kelas_tidak_bisa_dihapus(): void
    {
        $dosen = Dosen::factory()->create();
        KelasKuliah::factory()->create()->dosen()->attach($dosen);

        $this->delete(route('admin.dosen.destroy', $dosen))
            ->assertSessionHas('gagal', 'Dosen tidak bisa dihapus karena masih mengampu kelas.');

        $this->assertModelExists($dosen);
    }

    public function test_mahasiswa_yang_masih_terdaftar_di_kelas_tidak_bisa_dihapus(): void
    {
        $peserta = PesertaKelas::factory()->create();

        $this->delete(route('admin.mahasiswa.destroy', $peserta->mahasiswa))
            ->assertSessionHas('gagal', 'Mahasiswa tidak bisa dihapus karena masih terdaftar di kelas.');
    }

    public function test_menghapus_mahasiswa_ikut_menghapus_akunnya(): void
    {
        $mahasiswa = Mahasiswa::factory()->create();
        $user = $mahasiswa->user;

        $this->delete(route('admin.mahasiswa.destroy', $mahasiswa))->assertSessionHas('sukses');

        $this->assertModelMissing($mahasiswa);
        $this->assertModelMissing($user);
    }
}
