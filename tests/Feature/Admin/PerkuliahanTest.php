<?php

namespace Tests\Feature\Admin;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\NilaiAsesmen;
use App\Models\PeriodeAkademik;
use App\Models\PesertaKelas;
use App\Models\RencanaAsesmen;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skenario uji 6 (bagian 12 spesifikasi): Admin membuat kelas dan menentukan dosen/peserta,
 * lalu data kelas, dosen, dan peserta tersimpan. Termasuk periode akademik (bagian 7.6.6).
 */
class PerkuliahanTest extends TestCase
{
    use RefreshDatabase;

    private PeriodeAkademik $periode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periode = PeriodeAkademik::factory()->create(['tahun_akademik' => '2026/2027', 'semester_periode' => 'Ganjil']);
        $this->masukSebagai(Role::ADMIN);
    }

    public function test_admin_menambah_periode_akademik(): void
    {
        $this->post(route('admin.periode-akademik.store'), ['tahun_akademik' => '2026/2027', 'semester_periode' => 'Genap'])
            ->assertRedirect(route('admin.periode-akademik.index'));

        $this->get(route('admin.periode-akademik.index'))
            ->assertOk()
            // Periode terbaru di atas: Genap berjalan setelah Ganjil di tahun akademik yang sama.
            ->assertSeeInOrder(['Genap', 'Ganjil']);
    }

    public function test_format_dan_duplikasi_periode_akademik_ditolak(): void
    {
        $this->post(route('admin.periode-akademik.store'), ['tahun_akademik' => '2026-2027', 'semester_periode' => 'Ganjil'])
            ->assertSessionHasErrors(['tahun_akademik' => 'Tahun akademik ditulis seperti 2026/2027.']);

        $this->post(route('admin.periode-akademik.store'), ['tahun_akademik' => '2026/2028', 'semester_periode' => 'Ganjil'])
            ->assertSessionHasErrors(['tahun_akademik' => 'Tahun akademik harus dua tahun berurutan, mis. 2026/2027.']);

        $this->post(route('admin.periode-akademik.store'), ['tahun_akademik' => '2026/2027', 'semester_periode' => 'Ganjil'])
            ->assertSessionHasErrors(['tahun_akademik' => 'Periode 2026/2027 Ganjil sudah ada.']);
    }

    public function test_periode_yang_punya_kelas_tidak_bisa_dihapus(): void
    {
        KelasKuliah::factory()->for($this->periode)->create();

        $this->delete(route('admin.periode-akademik.destroy', $this->periode))
            ->assertSessionHas('gagal', 'Periode akademik tidak bisa dihapus karena sudah punya kelas.');
    }

    public function test_admin_membuat_kelas_lalu_menentukan_dosen_dan_peserta(): void
    {
        $mataKuliah = MataKuliah::factory()->create(['kode_mata_kuliah' => 'INF101', 'nama_mata_kuliah' => 'Matematika Diskrit']);
        $dosen = Dosen::factory()->create(['nama' => 'Nurul Hidayah, M.T.']);
        $mahasiswa = Mahasiswa::factory()->create(['nim' => '9992600001']);

        $this->post(route('admin.kelas-kuliah.store'), [
            'periode_akademik_id' => $this->periode->periode_akademik_id,
            'mata_kuliah_id' => $mataKuliah->mata_kuliah_id,
            'nama_kelas' => 'A',
        ]);
        $kelas = KelasKuliah::where('nama_kelas', 'A')->firstOrFail();

        $this->post(route('admin.kelas-kuliah.dosen.store', $kelas), ['dosen_id' => $dosen->dosen_id])
            ->assertRedirect(route('admin.kelas-kuliah.show', $kelas));
        $this->post(route('admin.kelas-kuliah.peserta.store', $kelas), ['mahasiswa_id' => $mahasiswa->mahasiswa_id])
            ->assertRedirect(route('admin.kelas-kuliah.show', $kelas));

        $this->assertTrue($kelas->dosen()->whereKey($dosen->dosen_id)->exists());
        $this->assertTrue($kelas->mahasiswa()->whereKey($mahasiswa->mahasiswa_id)->exists());

        $this->get(route('admin.kelas-kuliah.show', $kelas))
            ->assertOk()
            ->assertSee('Nurul Hidayah, M.T.')
            ->assertSee('9992600001');

        $this->get(route('admin.kelas-kuliah.index'))
            ->assertSee('Matematika Diskrit')
            ->assertSee('Nurul Hidayah, M.T.');
    }

    public function test_kelas_ganda_untuk_mata_kuliah_dan_periode_yang_sama_ditolak(): void
    {
        $kelas = KelasKuliah::factory()->for($this->periode)->create(['nama_kelas' => 'A']);

        $this->post(route('admin.kelas-kuliah.store'), [
            'periode_akademik_id' => $this->periode->periode_akademik_id,
            'mata_kuliah_id' => $kelas->mata_kuliah_id,
            'nama_kelas' => 'A',
        ])->assertSessionHasErrors(['nama_kelas' => 'Kelas ini sudah ada untuk mata kuliah dan periode yang sama.']);
    }

    public function test_daftar_kelas_mengikuti_periode_terpilih(): void
    {
        $periodeLalu = PeriodeAkademik::factory()->create(['tahun_akademik' => '2025/2026', 'semester_periode' => 'Genap']);
        KelasKuliah::factory()->for($this->periode)->for(MataKuliah::factory()->state(['nama_mata_kuliah' => 'Kelas Periode Ini']))->create();
        KelasKuliah::factory()->for($periodeLalu)->for(MataKuliah::factory()->state(['nama_mata_kuliah' => 'Kelas Periode Lalu']))->create();

        // Bawaan: periode aktif (periode terbaru yang punya kelas).
        $this->get(route('admin.kelas-kuliah.index'))
            ->assertSee('Kelas Periode Ini')
            ->assertDontSee('Kelas Periode Lalu');

        $this->get(route('admin.kelas-kuliah.index', ['periode' => $periodeLalu->periode_akademik_id]))
            ->assertSee('Kelas Periode Lalu')
            ->assertDontSee('Kelas Periode Ini');
    }

    public function test_mahasiswa_tidak_bisa_di_dua_kelas_mata_kuliah_yang_sama_dalam_satu_periode(): void
    {
        $kelasA = KelasKuliah::factory()->for($this->periode)->create(['nama_kelas' => 'A']);
        $kelasB = KelasKuliah::factory()->for($this->periode)->for($kelasA->mataKuliah)->create(['nama_kelas' => 'B']);
        $mahasiswa = Mahasiswa::factory()->create();
        $kelasA->mahasiswa()->attach($mahasiswa);

        $this->post(route('admin.kelas-kuliah.peserta.store', $kelasB), ['mahasiswa_id' => $mahasiswa->mahasiswa_id])
            ->assertSessionHasErrors(['mahasiswa_id' => 'Mahasiswa ini sudah terdaftar di kelas A untuk mata kuliah yang sama pada periode ini.']);

        $this->post(route('admin.kelas-kuliah.peserta.store', $kelasA), ['mahasiswa_id' => $mahasiswa->mahasiswa_id])
            ->assertSessionHasErrors(['mahasiswa_id' => 'Mahasiswa ini sudah terdaftar di kelas ini.']);
    }

    public function test_mahasiswa_boleh_mengulang_mata_kuliah_di_periode_lain(): void
    {
        $kelasLalu = KelasKuliah::factory()->for(PeriodeAkademik::factory()->state(['tahun_akademik' => '2025/2026']))->create();
        $kelasSekarang = KelasKuliah::factory()->for($this->periode)->for($kelasLalu->mataKuliah)->create();
        $mahasiswa = Mahasiswa::factory()->create();
        $kelasLalu->mahasiswa()->attach($mahasiswa);

        $this->post(route('admin.kelas-kuliah.peserta.store', $kelasSekarang), ['mahasiswa_id' => $mahasiswa->mahasiswa_id])
            ->assertSessionHasNoErrors();
    }

    public function test_tambah_peserta_per_angkatan_melewati_yang_sudah_terdaftar(): void
    {
        $kelasA = KelasKuliah::factory()->for($this->periode)->create(['nama_kelas' => 'A']);
        $kelasB = KelasKuliah::factory()->for($this->periode)->for($kelasA->mataKuliah)->create(['nama_kelas' => 'B']);
        $angkatan2025 = Mahasiswa::factory()->count(3)->create(['angkatan' => 2025]);
        Mahasiswa::factory()->create(['angkatan' => 2024]);
        $kelasA->mahasiswa()->attach($angkatan2025->first());

        $this->post(route('admin.kelas-kuliah.peserta-angkatan.store', $kelasB), ['angkatan' => 2025])
            ->assertSessionHas('sukses', '2 mahasiswa angkatan 2025 ditambahkan. 1 mahasiswa dilewati karena sudah terdaftar di kelas mata kuliah ini.');

        $this->assertSame(2, $kelasB->pesertaKelas()->count());
    }

    public function test_cari_mahasiswa_hanya_menampilkan_yang_belum_terdaftar(): void
    {
        $kelas = KelasKuliah::factory()->for($this->periode)->create();
        $terdaftar = Mahasiswa::factory()->create(['nama' => 'Budi Terdaftar']);
        Mahasiswa::factory()->create(['nama' => 'Budi Belum']);
        $kelas->mahasiswa()->attach($terdaftar);

        $this->get(route('admin.kelas-kuliah.show', [$kelas, 'cari_mahasiswa' => 'Budi']))
            ->assertSee('Budi Belum')
            ->assertSeeInOrder(['Hasil pencarian', 'Budi Belum', 'Daftar Peserta', 'Budi Terdaftar']);
    }

    public function test_peserta_yang_sudah_punya_nilai_tidak_bisa_dikeluarkan(): void
    {
        $nilai = NilaiAsesmen::factory()->create();
        $peserta = $nilai->pesertaKelas;

        $this->delete(route('admin.kelas-kuliah.peserta.destroy', [$peserta->kelasKuliah, $peserta]))
            ->assertSessionHas('gagal', 'Peserta tidak bisa dihapus karena sudah punya nilai.');

        $this->assertModelExists($peserta);
    }

    public function test_peserta_dan_dosen_bisa_dikeluarkan_dari_kelas(): void
    {
        $peserta = PesertaKelas::factory()->create();
        $kelas = $peserta->kelasKuliah;
        $dosen = Dosen::factory()->create();
        $kelas->dosen()->attach($dosen);

        $this->delete(route('admin.kelas-kuliah.peserta.destroy', [$kelas, $peserta]))->assertSessionHas('sukses');
        $this->delete(route('admin.kelas-kuliah.dosen.destroy', [$kelas, $dosen]))->assertSessionHas('sukses');

        $this->assertModelMissing($peserta);
        $this->assertFalse($kelas->dosen()->exists());
        $this->assertModelExists($dosen);
    }

    public function test_kelas_yang_punya_rencana_asesmen_tidak_bisa_dihapus(): void
    {
        $rencana = RencanaAsesmen::factory()->create();

        $this->delete(route('admin.kelas-kuliah.destroy', $rencana->kelasKuliah))
            ->assertSessionHas('gagal', 'Kelas tidak bisa dihapus karena sudah punya rencana asesmen.');
    }

    public function test_mata_kuliah_kelas_yang_sudah_punya_peserta_tidak_bisa_diganti(): void
    {
        $peserta = PesertaKelas::factory()->create();
        $kelas = $peserta->kelasKuliah;
        $mataKuliahLain = MataKuliah::factory()->create();

        $this->put(route('admin.kelas-kuliah.update', $kelas), [
            'periode_akademik_id' => $kelas->periode_akademik_id,
            'mata_kuliah_id' => $mataKuliahLain->mata_kuliah_id,
            'nama_kelas' => $kelas->nama_kelas,
        ])->assertSessionHasErrors(['mata_kuliah_id' => 'Mata kuliah dan periode tidak bisa diganti karena kelas sudah punya peserta atau rencana asesmen.']);

        // Nama kelas tetap bisa diganti.
        $this->put(route('admin.kelas-kuliah.update', $kelas), [
            'periode_akademik_id' => $kelas->periode_akademik_id,
            'mata_kuliah_id' => $kelas->mata_kuliah_id,
            'nama_kelas' => 'Z',
        ])->assertSessionHasNoErrors();
    }

    public function test_peserta_dari_kelas_lain_tidak_bisa_dihapus_lewat_alamat_kelas_ini(): void
    {
        $kelas = KelasKuliah::factory()->create();
        $pesertaKelasLain = PesertaKelas::factory()->create();

        $this->delete(route('admin.kelas-kuliah.peserta.destroy', [$kelas, $pesertaKelasLain]))->assertNotFound();
    }

    public function test_peran_selain_admin_tidak_bisa_mengatur_kelas(): void
    {
        $kelas = KelasKuliah::factory()->create();
        $this->masukSebagai(Role::DOSEN);

        $this->get(route('admin.kelas-kuliah.index'))->assertForbidden();
        $this->post(route('admin.kelas-kuliah.peserta-angkatan.store', $kelas), ['angkatan' => 2025])->assertForbidden();
    }
}
