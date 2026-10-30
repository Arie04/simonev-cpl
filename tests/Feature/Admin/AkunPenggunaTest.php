<?php

namespace Tests\Feature\Admin;

use App\Models\Dosen;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Menu Akun Pengguna: daftar, tambah/ubah, atur peran, reset password, dan hapus (KD-36).
 */
class AkunPenggunaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->masukSebagai(Role::ADMIN);
    }

    public function test_admin_menambah_akun_koprodi(): void
    {
        $this->post(route('admin.akun.store'), [
            'nama' => 'Koordinator Baru',
            'email' => 'koprodi.baru@unkhair.ac.id',
            'password' => 'rahasia123',
            'peran' => [Role::KOPRODI],
        ])->assertRedirect(route('admin.akun.index'));

        $user = User::where('email', 'koprodi.baru@unkhair.ac.id')->firstOrFail();
        $this->assertSame([Role::KOPRODI], $user->roles->pluck('kode_role')->all());

        $this->get(route('admin.akun.index'))
            ->assertSee('Koordinator Baru')
            ->assertSee('Koordinator Program Studi');
    }

    public function test_akun_tanpa_data_dosen_atau_mahasiswa_wajib_punya_email_dan_peran(): void
    {
        $this->post(route('admin.akun.store'), [
            'nama' => 'Tanpa Email',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors([
            'email' => 'Email wajib diisi untuk akun yang tidak punya data dosen atau mahasiswa.',
            'peran' => 'Pilih minimal satu peran.',
        ]);
    }

    public function test_peran_dosen_tidak_bisa_diberikan_ke_akun_tanpa_data_dosen(): void
    {
        $this->post(route('admin.akun.store'), [
            'nama' => 'Bukan Dosen',
            'email' => 'bukan.dosen@unkhair.ac.id',
            'password' => 'rahasia123',
            'peran' => [Role::DOSEN],
        ])->assertSessionHasErrors('peran.0');

        $this->assertDatabaseMissing('users', ['email' => 'bukan.dosen@unkhair.ac.id']);
    }

    public function test_admin_menambahkan_peran_koprodi_ke_akun_dosen(): void
    {
        $dosen = Dosen::factory()->create();

        $this->get(route('admin.akun.edit', $dosen->user))
            ->assertOk()
            ->assertSee('dosen dengan NIDN')
            ->assertSee('Dosen');

        $this->put(route('admin.akun.update', $dosen->user), [
            'nama' => $dosen->nama,
            'email' => '',
            'peran' => [Role::DOSEN, Role::KOPRODI],
        ])->assertRedirect(route('admin.akun.index'));

        $this->assertEqualsCanonicalizing([Role::DOSEN, Role::KOPRODI], $dosen->user->roles()->pluck('kode_role')->all());
    }

    public function test_mengubah_nama_akun_dosen_ikut_mengubah_data_dosen(): void
    {
        $dosen = Dosen::factory()->create();

        $this->put(route('admin.akun.update', $dosen->user), [
            'nama' => 'Nama Dari Menu Akun',
            'peran' => [Role::DOSEN],
        ])->assertSessionHasNoErrors();

        $this->assertSame('Nama Dari Menu Akun', $dosen->fresh()->nama);
    }

    public function test_admin_tidak_bisa_mencabut_peran_admin_miliknya_sendiri(): void
    {
        $this->put(route('admin.akun.update', $this->admin), [
            'nama' => $this->admin->nama,
            'email' => $this->admin->email,
            'peran' => [Role::KOPRODI],
        ])->assertSessionHasErrors(['peran' => 'Anda tidak bisa mencabut peran Admin dari akun Anda sendiri.']);

        $this->assertTrue($this->admin->fresh()->punyaPeran(Role::ADMIN));
    }

    public function test_admin_mereset_password_akun_lain(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI)->create();

        $this->put(route('admin.akun.password.update', $user), [
            'password_baru' => 'passwordbaru',
            'password_baru_confirmation' => 'passwordbaru',
        ])->assertRedirect(route('admin.akun.edit', $user))
            ->assertSessionHas('sukses', "Password {$user->nama} berhasil direset.");

        $this->assertTrue(Hash::check('passwordbaru', $user->fresh()->password));
    }

    public function test_reset_password_harus_dikonfirmasi(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI)->create();

        $this->put(route('admin.akun.password.update', $user), [
            'password_baru' => 'passwordbaru',
            'password_baru_confirmation' => 'beda',
        ])->assertSessionHasErrors(['password_baru' => 'Konfirmasi password baru tidak cocok.']);
    }

    public function test_akun_tanpa_data_dosen_atau_mahasiswa_bisa_dihapus(): void
    {
        $user = User::factory()->denganPeran(Role::KOPRODI)->create();

        $this->delete(route('admin.akun.destroy', $user))->assertSessionHas('sukses', 'Akun berhasil dihapus.');

        $this->assertModelMissing($user);
        $this->assertDatabaseMissing('user_roles', ['user_id' => $user->user_id]);
    }

    public function test_akun_milik_dosen_dihapus_lewat_data_dosen(): void
    {
        $dosen = Dosen::factory()->create();

        $this->delete(route('admin.akun.destroy', $dosen->user))
            ->assertSessionHas('gagal', 'Akun ini terhubung ke data dosen atau mahasiswa. Hapus lewat menu Data Master Dosen atau Mahasiswa.');

        $this->assertModelExists($dosen->user);
    }

    public function test_admin_tidak_bisa_menghapus_akunnya_sendiri(): void
    {
        $this->delete(route('admin.akun.destroy', $this->admin))
            ->assertSessionHas('gagal', 'Anda tidak bisa menghapus akun Anda sendiri.');

        $this->assertModelExists($this->admin);
    }

    public function test_pencarian_akun_dengan_nidn(): void
    {
        $userDosen = User::factory()->denganPeran(Role::DOSEN)->create(['nama' => 'Dosen Dicari']);
        Dosen::factory()->for($userDosen)->create(['nidn' => '0099887766', 'nama' => 'Dosen Dicari']);
        User::factory()->denganPeran(Role::KOPRODI)->create(['nama' => 'Koprodi Lain']);

        $this->get(route('admin.akun.index', ['cari' => '0099887766']))
            ->assertSee('Dosen Dicari')
            ->assertDontSee('Koprodi Lain');
    }
}
