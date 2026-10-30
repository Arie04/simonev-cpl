<?php

namespace Tests\Feature\Dosen;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\NilaiAsesmen;
use App\Models\PesertaKelas;
use App\Models\RencanaAsesmen;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skenario uji 9 dan 10 (bagian 12 spesifikasi): dosen memasukkan nilai peserta kelas; nilai
 * di atas nilai maksimum ditolak. Termasuk aturan simpan sekaligus (bagian 7.6.8) dan nilai
 * kosong (KD-29).
 */
class InputNilaiTest extends TestCase
{
    use RefreshDatabase;

    private KelasKuliah $kelas;

    private RencanaAsesmen $asesmen;

    private PesertaKelas $peserta1;

    private PesertaKelas $peserta2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kelas = KelasKuliah::factory()->create(['nama_kelas' => 'A']);
        $this->asesmen = RencanaAsesmen::factory()->for($this->kelas)->create(['nama_asesmen' => 'UTS', 'nilai_maksimum' => 50]);
        $this->peserta1 = PesertaKelas::factory()->for($this->kelas)->for(Mahasiswa::factory()->state(['nim' => '9992600001', 'nama' => 'Putra Wijaya']))->create();
        $this->peserta2 = PesertaKelas::factory()->for($this->kelas)->for(Mahasiswa::factory()->state(['nim' => '9992600002', 'nama' => 'Qori Amalia']))->create();

        $dosen = Dosen::factory()->create();
        $this->kelas->dosen()->attach($dosen);
        $this->masukSebagai(Role::DOSEN, $dosen->user);
    }

    private function simpan(array $nilai)
    {
        return $this->from(route('dosen.nilai.edit', ['kelas' => $this->kelas->kelas_kuliah_id, 'asesmen' => $this->asesmen->rencana_asesmen_id]))
            ->put(route('dosen.nilai.update', $this->asesmen), ['nilai' => $nilai]);
    }

    public function test_dosen_memasukkan_nilai_seluruh_peserta_sekaligus(): void
    {
        $this->get(route('dosen.nilai.edit', ['kelas' => $this->kelas->kelas_kuliah_id, 'asesmen' => $this->asesmen->rencana_asesmen_id]))
            ->assertOk()
            ->assertSee('Putra Wijaya')
            ->assertSee('Qori Amalia')
            ->assertSee('Belum');

        $this->simpan([
            $this->peserta1->peserta_kelas_id => '42,5',
            $this->peserta2->peserta_kelas_id => '50',
        ])->assertRedirect(route('dosen.nilai.edit', ['kelas' => $this->kelas->kelas_kuliah_id, 'asesmen' => $this->asesmen->rencana_asesmen_id]))
            ->assertSessionHas('sukses', 'Nilai UTS berhasil disimpan: 2 terisi, 0 belum diinput.');

        $this->assertDatabaseHas('nilai_asesmen', ['peserta_kelas_id' => $this->peserta1->peserta_kelas_id, 'nilai' => 42.5]);
        $this->assertDatabaseHas('nilai_asesmen', ['peserta_kelas_id' => $this->peserta2->peserta_kelas_id, 'nilai' => 50]);

        $this->get(route('dosen.nilai.edit', ['kelas' => $this->kelas->kelas_kuliah_id, 'asesmen' => $this->asesmen->rencana_asesmen_id]))
            ->assertSee('value="42,5"', false)
            ->assertSee('Tersimpan')
            ->assertSee('2 dari 2 peserta sudah dinilai');
    }

    public function test_nilai_di_atas_nilai_maksimum_ditolak_dan_tidak_ada_yang_disimpan(): void
    {
        $this->simpan([
            $this->peserta1->peserta_kelas_id => '45',
            $this->peserta2->peserta_kelas_id => '51',
        ])->assertSessionHasErrors([
            "nilai.{$this->peserta2->peserta_kelas_id}" => 'Nilai tidak boleh lebih dari nilai maksimum (50).',
        ]);

        $this->assertDatabaseCount('nilai_asesmen', 0);
    }

    public function test_nilai_negatif_dan_bukan_angka_ditolak(): void
    {
        $this->simpan([
            $this->peserta1->peserta_kelas_id => '-1',
            $this->peserta2->peserta_kelas_id => 'A',
        ])->assertSessionHasErrors([
            "nilai.{$this->peserta1->peserta_kelas_id}" => 'Nilai tidak boleh kurang dari 0.',
            "nilai.{$this->peserta2->peserta_kelas_id}" => 'Nilai harus berupa angka.',
        ]);
    }

    public function test_baris_yang_salah_ditandai_dan_isian_lain_tidak_hilang(): void
    {
        $this->simpan([
            $this->peserta1->peserta_kelas_id => '45',
            $this->peserta2->peserta_kelas_id => '99',
        ]);

        $this->get(route('dosen.nilai.edit', ['kelas' => $this->kelas->kelas_kuliah_id, 'asesmen' => $this->asesmen->rencana_asesmen_id]))
            ->assertSee('Ada nilai yang tidak valid, jadi tidak ada nilai yang disimpan.')
            ->assertSee('value="45"', false)
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_mengosongkan_nilai_berarti_menghapus_nilai_yang_tersimpan(): void
    {
        NilaiAsesmen::factory()->for($this->asesmen)->for($this->peserta1)->create(['nilai' => 30]);

        $this->simpan([
            $this->peserta1->peserta_kelas_id => '',
            $this->peserta2->peserta_kelas_id => '40',
        ])->assertSessionHas('sukses', 'Nilai UTS berhasil disimpan: 1 terisi, 1 belum diinput.');

        $this->assertDatabaseMissing('nilai_asesmen', ['peserta_kelas_id' => $this->peserta1->peserta_kelas_id]);
        $this->assertDatabaseCount('nilai_asesmen', 1);
    }

    public function test_mahasiswa_di_luar_kelas_tidak_bisa_dinilai(): void
    {
        $pesertaKelasLain = PesertaKelas::factory()->create();

        $this->simpan([$pesertaKelasLain->peserta_kelas_id => '40'])
            ->assertSessionHasErrors(['nilai' => 'Mahasiswa yang bisa dinilai hanya peserta kelas ini.']);

        $this->assertDatabaseCount('nilai_asesmen', 0);
    }

    public function test_dosen_tidak_bisa_menginput_nilai_kelas_yang_tidak_diampu(): void
    {
        $asesmenKelasLain = RencanaAsesmen::factory()->create();
        $peserta = PesertaKelas::factory()
            ->for($asesmenKelasLain->kelasKuliah)
            ->for(Mahasiswa::factory()->state(['nama' => 'Peserta Kelas Lain']))
            ->create();

        $this->put(route('dosen.nilai.update', $asesmenKelasLain), ['nilai' => [$peserta->peserta_kelas_id => '50']])
            ->assertForbidden();

        $this->get(route('dosen.nilai.edit', ['kelas' => $asesmenKelasLain->kelas_kuliah_id, 'asesmen' => $asesmenKelasLain->rencana_asesmen_id]))
            ->assertOk()
            ->assertDontSee('Peserta Kelas Lain');
    }

    public function test_filter_bertingkat_hanya_berisi_kelas_yang_diampu(): void
    {
        // Nama ditulis langsung karena MataKuliahFactory memilih nama secara acak dari lima nama.
        $this->kelas->mataKuliah->update(['nama_mata_kuliah' => 'Mata Kuliah Saya']);
        KelasKuliah::factory()->for(MataKuliah::factory()->state(['nama_mata_kuliah' => 'Mata Kuliah Orang Lain']))->create();

        $this->get(route('dosen.nilai.edit'))
            ->assertOk()
            ->assertSee('Mata Kuliah Saya')
            ->assertDontSee('Mata Kuliah Orang Lain');
    }

    public function test_pilihan_yang_hanya_satu_dipilih_otomatis(): void
    {
        // Dosen hanya mengampu satu kelas dengan satu asesmen: tabel nilai langsung tampil.
        $this->get(route('dosen.nilai.edit'))
            ->assertSee('UTS · '.$this->kelas->nama())
            ->assertSee('Simpan Nilai');
    }

    public function test_peran_lain_tidak_bisa_membuka_input_nilai(): void
    {
        $this->masukSebagai(Role::MAHASISWA);

        $this->get(route('dosen.nilai.edit'))->assertForbidden();
        $this->put(route('dosen.nilai.update', $this->asesmen), ['nilai' => []])->assertForbidden();
    }
}
