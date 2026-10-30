<?php

namespace Tests\Feature\Admin;

use App\Models\BahanKajian;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use App\Models\Profesi;
use App\Models\ProfilLulusan;
use App\Models\ProgramStudi;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Skenario uji 4 (bagian 12 spesifikasi): Admin menambahkan data master kurikulum,
 * lalu data itu tersimpan dan tampil.
 */
class DataMasterKurikulumTest extends TestCase
{
    use RefreshDatabase;

    private Kurikulum $kurikulum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kurikulum = Kurikulum::factory()->create(['tahun_berlaku' => 2026, 'nama_kurikulum' => 'Kurikulum 2026']);
        $this->masukSebagai(Role::ADMIN);
    }

    /**
     * Data yang bergantung pada kurikulum: route, tabel, isian form, dan teks yang harus tampil.
     */
    public static function dataKurikulum(): array
    {
        return [
            'evaluasi kurikulum' => ['evaluasi-kurikulum', 'evaluasi_kurikulum', [
                'tahap_evaluasi' => 'Evaluasi kurikulum 2021',
                'kinerja_mutu' => 'Rata-rata masa studi 4,5 tahun',
                'standar_kinerja_mutu' => 'Masa studi maksimal 4 tahun',
                'kesenjangan' => 'Masa studi melebihi standar',
                'tindak_lanjut' => 'Penyederhanaan prasyarat MK',
            ], 'Evaluasi kurikulum 2021'],
            'profil lulusan' => ['profil-lulusan', 'profil_lulusan', [
                'kode_pl' => 'PL01',
                'deskripsi_pl' => 'Pengembang perangkat lunak',
                'unsur' => 'Ilmu Komputer',
                'sumber' => 'Asosiasi',
            ], 'Pengembang perangkat lunak'],
            'profesi' => ['profesi', 'profesi', [
                'nama_profesi' => 'Software Engineer',
            ], 'Software Engineer'],
            'CPL' => ['cpl', 'cpl', [
                'kode_cpl' => 'CPL01',
                'kategori_cpl' => 'Sikap',
                'deskripsi_cpl' => 'Bertakwa kepada Tuhan Yang Maha Esa',
            ], 'Bertakwa kepada Tuhan Yang Maha Esa'],
            'bahan kajian' => ['bahan-kajian', 'bahan_kajian', [
                'kode_bk' => 'BK22',
                'nama_bahan_kajian' => 'Metodologi',
                'kategori_bk' => 'Kompetensi Utama',
                'bobot_min' => 2,
                'bobot_max' => 6,
                'cs_core' => 17,
                'ka_core' => 14,
                'referensi' => 'CS2023',
            ], 'Metodologi'],
            'mata kuliah' => ['mata-kuliah', 'mata_kuliah', [
                'kode_mata_kuliah' => 'INF101',
                'nama_mata_kuliah' => 'Matematika Diskrit',
                'semester' => 1,
                'jumlah_sks' => 3,
                'jenis_mata_kuliah' => 'Wajib',
                'level_bloom_mk' => 'C3',
            ], 'Matematika Diskrit'],
        ];
    }

    #[DataProvider('dataKurikulum')]
    public function test_admin_menambahkan_data_kurikulum_lalu_data_tersimpan_dan_tampil(
        string $route,
        string $tabel,
        array $isian,
        string $teksTampil,
    ): void {
        $this->get(route("admin.{$route}.create"))
            ->assertOk()
            ->assertSee('Kurikulum 2026');

        $this->post(route("admin.{$route}.store"), [...$isian, 'kurikulum_id' => $this->kurikulum->kurikulum_id])
            ->assertRedirect(route("admin.{$route}.index"))
            ->assertSessionHas('sukses');

        $this->assertDatabaseHas($tabel, [...$isian, 'kurikulum_id' => $this->kurikulum->kurikulum_id]);

        $this->get(route("admin.{$route}.index"))
            ->assertOk()
            ->assertSee($teksTampil);
    }

    public function test_admin_menambahkan_program_studi_dan_kurikulum(): void
    {
        $this->post(route('admin.program-studi.store'), [
            'nama_perguruan_tinggi' => 'Universitas Khairun',
            'nama_fakultas' => 'Fakultas Teknik',
            'nama_program_studi' => 'Teknik Informatika',
            'jenjang_pendidikan' => 'S1',
            'gelar_lulusan' => 'S.Kom.',
        ])->assertRedirect(route('admin.program-studi.index'));

        $programStudi = ProgramStudi::where('nama_program_studi', 'Teknik Informatika')->firstOrFail();

        $this->post(route('admin.kurikulum.store'), [
            'program_studi_id' => $programStudi->program_studi_id,
            'nama_kurikulum' => 'Kurikulum OBE 2030',
            'tahun_berlaku' => 2030,
        ])->assertRedirect(route('admin.kurikulum.index'));

        $this->get(route('admin.kurikulum.index'))
            ->assertSee('Kurikulum OBE 2030')
            ->assertSee('Teknik Informatika');
    }

    public function test_tahun_berlaku_kurikulum_tidak_boleh_ganda(): void
    {
        $this->post(route('admin.kurikulum.store'), [
            'program_studi_id' => $this->kurikulum->program_studi_id,
            'nama_kurikulum' => 'Kurikulum Kembar',
            'tahun_berlaku' => 2026,
        ])->assertSessionHasErrors(['tahun_berlaku' => 'Sudah ada kurikulum dengan tahun berlaku ini.']);
    }

    public function test_admin_menambahkan_cpmk_ke_cpl_di_kurikulum_yang_sama(): void
    {
        $cpl = Cpl::factory()->for($this->kurikulum)->create(['kode_cpl' => 'CPL06']);

        $this->post(route('admin.cpmk.store'), [
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'cpl_id' => $cpl->cpl_id,
            'kode_cpmk' => 'CPMK061',
            'deskripsi_cpmk' => 'Mampu merancang basis data',
        ])->assertRedirect(route('admin.cpmk.index'));

        $this->assertDatabaseHas('cpmk', ['cpl_id' => $cpl->cpl_id, 'kode_cpmk' => 'CPMK061']);
        $this->get(route('admin.cpmk.index'))->assertSee('CPMK061')->assertSee('CPL06');
    }

    public function test_cpmk_tidak_bisa_memakai_cpl_dari_kurikulum_lain(): void
    {
        $cplKurikulumLain = Cpl::factory()->create();

        $this->post(route('admin.cpmk.store'), [
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'cpl_id' => $cplKurikulumLain->cpl_id,
            'kode_cpmk' => 'CPMK061',
            'deskripsi_cpmk' => 'Mampu merancang basis data',
        ])->assertSessionHasErrors('cpl_id');

        $this->assertDatabaseCount('cpmk', 0);
    }

    public function test_profil_lulusan_dihubungkan_ke_profesi_dari_kurikulum_yang_sama(): void
    {
        $profesi = Profesi::factory()->for($this->kurikulum)->create(['nama_profesi' => 'Data Analyst']);
        $profesiKurikulumLain = Profesi::factory()->create();

        $isian = [
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'kode_pl' => 'PL02',
            'deskripsi_pl' => 'Analis data',
        ];

        $this->post(route('admin.profil-lulusan.store'), [...$isian, 'profesi' => [$profesiKurikulumLain->profesi_id]])
            ->assertSessionHasErrors(['profesi.0' => 'Profesi yang dipilih tidak valid.']);

        $this->post(route('admin.profil-lulusan.store'), [...$isian, 'profesi' => [$profesi->profesi_id]])
            ->assertRedirect(route('admin.profil-lulusan.index'));

        $this->assertSame(['Data Analyst'], ProfilLulusan::where('kode_pl', 'PL02')->first()->profesi->pluck('nama_profesi')->all());
        $this->get(route('admin.profesi.index'))->assertSee('PL02');
    }

    public function test_data_baru_masuk_ke_kurikulum_yang_dipilih_di_dropdown(): void
    {
        $kurikulumLama = Kurikulum::factory()->create(['tahun_berlaku' => 2021, 'nama_kurikulum' => 'Kurikulum 2021']);

        // Bawaan: kurikulum terbaru. Setelah memilih Kurikulum 2021, pilihan itu diingat.
        $this->get(route('admin.cpl.index'))->assertSee('Belum ada data CPL di kurikulum ini.');
        $this->get(route('admin.cpl.index', ['kurikulum' => $kurikulumLama->kurikulum_id]))->assertOk();

        $this->get(route('admin.cpl.create'))
            ->assertSee('Kurikulum 2021')
            ->assertSee('value="'.$kurikulumLama->kurikulum_id.'"', false);
    }

    public function test_daftar_hanya_menampilkan_data_kurikulum_terpilih(): void
    {
        Cpl::factory()->for($this->kurikulum)->create(['kode_cpl' => 'CPL-BARU']);
        Cpl::factory()->for(Kurikulum::factory()->create(['tahun_berlaku' => 2021]))->create(['kode_cpl' => 'CPL-LAMA']);

        $this->get(route('admin.cpl.index'))
            ->assertSee('CPL-BARU')
            ->assertDontSee('CPL-LAMA');
    }

    public function test_bobot_maksimum_bahan_kajian_tidak_boleh_lebih_kecil_dari_bobot_minimum(): void
    {
        $this->post(route('admin.bahan-kajian.store'), [
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'kode_bk' => 'BK01',
            'nama_bahan_kajian' => 'Algoritma',
            'bobot_min' => 5,
            'bobot_max' => 3,
        ])->assertSessionHasErrors(['bobot_max' => 'Bobot maksimum tidak boleh lebih kecil dari bobot minimum.']);
    }

    public function test_cs_core_kosong_dan_nol_disimpan_berbeda(): void
    {
        $isian = ['kurikulum_id' => $this->kurikulum->kurikulum_id, 'nama_bahan_kajian' => 'Etika'];

        $this->post(route('admin.bahan-kajian.store'), [...$isian, 'kode_bk' => 'BK03', 'cs_core' => ''])->assertSessionHasNoErrors();
        $this->post(route('admin.bahan-kajian.store'), [...$isian, 'kode_bk' => 'BK05', 'cs_core' => '0'])->assertSessionHasNoErrors();

        $this->assertNull(BahanKajian::where('kode_bk', 'BK03')->value('cs_core'));
        $this->assertSame(0, BahanKajian::where('kode_bk', 'BK05')->first()->cs_core);
    }

    public function test_semester_dan_sks_mata_kuliah_dibatasi(): void
    {
        $this->post(route('admin.mata-kuliah.store'), [
            'kurikulum_id' => $this->kurikulum->kurikulum_id,
            'kode_mata_kuliah' => 'INF999',
            'nama_mata_kuliah' => 'Skripsi',
            'semester' => 9,
            'jumlah_sks' => 25,
            'jenis_mata_kuliah' => 'Wajib',
        ])->assertSessionHasErrors([
            'semester' => 'Semester harus bernilai 1 sampai 8.',
            'jumlah_sks' => 'Jumlah SKS harus bernilai 1 sampai 24.',
        ]);
    }

    public function test_admin_mengubah_data_tanpa_memindah_kurikulumnya(): void
    {
        $cpl = Cpl::factory()->for($this->kurikulum)->create(['kode_cpl' => 'CPL01']);
        $kurikulumLain = Kurikulum::factory()->create();

        $this->put(route('admin.cpl.update', $cpl), [
            'kurikulum_id' => $kurikulumLain->kurikulum_id,
            'kode_cpl' => 'CPL01',
            'kategori_cpl' => 'Pengetahuan',
            'deskripsi_cpl' => 'Deskripsi baru',
        ])->assertRedirect(route('admin.cpl.index'));

        $cpl->refresh();
        $this->assertSame('Deskripsi baru', $cpl->deskripsi_cpl);
        $this->assertSame($this->kurikulum->kurikulum_id, $cpl->kurikulum_id);
    }

    public function test_halaman_tambah_meminta_kurikulum_jika_belum_ada_kurikulum(): void
    {
        $this->kurikulum->delete();

        $this->get(route('admin.cpl.index'))->assertSee('Belum ada kurikulum. Tambahkan kurikulum terlebih dahulu.');
        $this->get(route('admin.cpl.create'))->assertRedirect(route('admin.kurikulum.create'));
    }

    public function test_pencarian_dan_jumlah_baris_per_halaman(): void
    {
        foreach (range(1, 12) as $nomor) {
            MataKuliah::factory()->for($this->kurikulum)->create([
                'kode_mata_kuliah' => sprintf('INF%03d', $nomor),
                'nama_mata_kuliah' => $nomor === 7 ? 'Basis Data Lanjut' : "Mata Kuliah {$nomor}",
                'semester' => 1,
            ]);
        }

        $this->get(route('admin.mata-kuliah.index'))
            ->assertSee('Menampilkan 1–10 dari 12 data')
            ->assertDontSee('INF011');

        $this->get(route('admin.mata-kuliah.index', ['per_halaman' => 25]))
            ->assertSee('Menampilkan 1–12 dari 12 data');

        $this->get(route('admin.mata-kuliah.index', ['cari' => 'basis data']))
            ->assertSee('Basis Data Lanjut')
            ->assertSee('Menampilkan 1–1 dari 1 data');
    }

    public function test_cpmk_hanya_tampil_di_kurikulumnya(): void
    {
        Cpmk::factory()->for(Cpl::factory()->for($this->kurikulum))->create(['kode_cpmk' => 'CPMK-INI']);
        Cpmk::factory()->create(['kode_cpmk' => 'CPMK-LAIN']);

        $this->get(route('admin.cpmk.index', ['kurikulum' => $this->kurikulum->kurikulum_id]))
            ->assertSee('CPMK-INI')
            ->assertDontSee('CPMK-LAIN');
    }
}
