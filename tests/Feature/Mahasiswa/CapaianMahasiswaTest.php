<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeAkademik;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembangunDataCapaian;
use Tests\TestCase;

/**
 * Skenario uji 15 (bagian 12 spesifikasi): mahasiswa membuka halaman capaian pribadi, lalu hanya
 * nilai dan capaian miliknya yang tampil (bagian 7.6.10, KF-15).
 */
class CapaianMahasiswaTest extends TestCase
{
    use MembangunDataCapaian;
    use RefreshDatabase;

    private Kurikulum $kurikulum;

    private MataKuliah $mataKuliah;

    private Mahasiswa $mahasiswa;

    private Mahasiswa $temanSekelas;

    protected function setUp(): void
    {
        parent::setUp();

        config(['capaian.ambang_kelulusan_mahasiswa' => 50.01, 'capaian.ambang_ketercapaian' => 85]);

        $this->kurikulum = Kurikulum::factory()->create(['tahun_berlaku' => 2026, 'nama_kurikulum' => 'Kurikulum OBE 2026']);
        $this->mataKuliah = $this->buatMataKuliah($this->kurikulum, 'INF101', [
            'CPMK011' => ['cpl' => 'CPL01', 'bobot' => 50, 'sub' => ['Sub-CPMK0111' => 50]],
            'CPMK021' => ['cpl' => 'CPL02', 'bobot' => 50, 'sub' => ['Sub-CPMK0211' => 50]],
        ]);
        $kelas = $this->buatKelas($this->mataKuliah, [
            'UTS' => ['bobot' => 50, 'maks' => 100, 'kontribusi' => ['Sub-CPMK0111' => 50]],
            'UAS' => ['bobot' => 50, 'maks' => 100, 'kontribusi' => ['Sub-CPMK0211' => 50]],
        ]);

        $this->mahasiswa = Mahasiswa::factory()->create(['nama' => 'Aisyah Putri', 'nim' => '9992300002']);
        $this->temanSekelas = Mahasiswa::factory()->create(['nama' => 'Bagas Pratama', 'nim' => '9992300003']);
        $this->beriNilai($kelas, $this->mahasiswa, ['UTS' => 80, 'UAS' => 40]);
        $this->beriNilai($kelas, $this->temanSekelas, ['UTS' => 67, 'UAS' => 99]);
    }

    private function bukaSebagaiMahasiswa(array $parameter = [])
    {
        $this->masukSebagai(Role::MAHASISWA, $this->mahasiswa->user);

        return $this->get(route('mahasiswa.capaian', $parameter));
    }

    public function test_mahasiswa_hanya_melihat_nilai_dan_capaian_miliknya(): void
    {
        $this->bukaSebagaiMahasiswa()
            ->assertOk()
            ->assertSee('Aisyah Putri')
            ->assertSee('NPM 9992300002')
            ->assertSeeInOrder(['UTS', '80', 'dari 100', 'UAS', '40', 'dari 100'])
            ->assertDontSee('Bagas Pratama')
            ->assertDontSee('9992300003')
            ->assertDontSee('>67<', false)
            ->assertDontSee('>99<', false);
    }

    public function test_capaian_sub_cpmk_cpmk_dan_cpl_dengan_status_lulus(): void
    {
        $this->bukaSebagaiMahasiswa()
            ->assertSeeInOrder(['Rata-rata Sub-CPMK', '60,00', 'Rata-rata CPMK', '60,00', 'Rata-rata CPL Kumulatif', '60,00'])
            ->assertSeeInOrder(['Capaian Sub-CPMK', 'Sub-CPMK0111', '80,00', 'Lulus', 'Sub-CPMK0211', '40,00', 'Belum Lulus'])
            ->assertSeeInOrder(['Capaian CPMK', 'CPMK011', '80,00', 'Lulus', 'CPMK021', '40,00', 'Belum Lulus'])
            ->assertSeeInOrder(['CPL Kumulatif · Kurikulum OBE 2026', 'CPL01', '80,00', 'CPL02', '40,00']);
    }

    public function test_nilai_yang_belum_diinput_tampil_belum_ada_nilai(): void
    {
        $kelasBaru = $this->buatKelas(
            $this->buatMataKuliah($this->kurikulum, 'INF102', ['CPMK041' => ['cpl' => 'CPL04', 'bobot' => 100, 'sub' => ['Sub-CPMK0411' => 100]]]),
            ['Kuis 1' => ['bobot' => 100, 'maks' => 20, 'kontribusi' => ['Sub-CPMK0411' => 100]]],
            PeriodeAkademik::where('tahun_akademik', '2026/2027')->first(),
        );
        $this->beriNilai($kelasBaru, $this->mahasiswa, []);

        $this->bukaSebagaiMahasiswa(['mata_kuliah' => $kelasBaru->mata_kuliah_id])
            ->assertSee('Kuis 1')
            ->assertSee('Belum ada nilai');
    }

    public function test_cpl_kumulatif_dipisah_per_kurikulum(): void
    {
        $kurikulumLama = Kurikulum::factory()->create(['tahun_berlaku' => 2021, 'nama_kurikulum' => 'Kurikulum 2021']);
        $kelasLama = $this->buatKelas(
            $this->buatMataKuliah($kurikulumLama, 'INF301', ['CPMK031' => ['cpl' => 'CPL01', 'bobot' => 100, 'sub' => ['Sub-CPMK0311' => 100]]]),
            ['Proyek' => ['bobot' => 100, 'maks' => 100, 'kontribusi' => ['Sub-CPMK0311' => 100]]],
            PeriodeAkademik::factory()->create(['tahun_akademik' => '2025/2026', 'semester_periode' => 'Genap']),
        );
        $this->beriNilai($kelasLama, $this->mahasiswa, ['Proyek' => 70]);

        // CPL01 kurikulum 2021 (70) tidak digabung dengan CPL01 kurikulum 2026 (80).
        $this->bukaSebagaiMahasiswa()
            ->assertSeeInOrder(['CPL Kumulatif · Kurikulum OBE 2026', 'CPL01', '80,00'])
            ->assertSeeInOrder(['CPL Kumulatif · Kurikulum 2021', 'CPL01', '70,00']);
    }

    public function test_pilihan_mata_kuliah_hanya_dari_kelas_yang_diikuti(): void
    {
        $mataKuliahLain = $this->buatMataKuliah($this->kurikulum, 'INF999', ['CPMK099' => ['cpl' => 'CPL09', 'bobot' => 100, 'sub' => ['Sub-CPMK0999' => 100]]]);
        $kelasLain = $this->buatKelas($mataKuliahLain, ['UAS' => ['bobot' => 100, 'maks' => 100, 'kontribusi' => ['Sub-CPMK0999' => 100]]]);
        $this->beriNilai($kelasLain, $this->temanSekelas, ['UAS' => 55]);

        // Mengetik id MK yang tidak diikuti di alamat tidak membuka datanya.
        $this->bukaSebagaiMahasiswa(['mata_kuliah' => $mataKuliahLain->mata_kuliah_id])
            ->assertOk()
            ->assertSee('INF101')
            ->assertDontSee('INF999')
            ->assertDontSee('Sub-CPMK0999');
    }

    public function test_status_ambang_belum_ditetapkan(): void
    {
        config(['capaian.ambang_kelulusan_mahasiswa' => null]);

        $this->bukaSebagaiMahasiswa()
            ->assertSee('<strong class="text-text">Belum ditetapkan</strong>', false)
            ->assertSee('Ambang belum ditetapkan')
            ->assertDontSee('Belum Lulus');
    }

    public function test_halaman_mahasiswa_tidak_punya_aksi_ubah(): void
    {
        $this->bukaSebagaiMahasiswa()
            ->assertDontSee('aria-label="Ubah', false)
            ->assertDontSee('aria-label="Hapus', false)
            ->assertDontSee('Simpan');
    }

    public function test_mahasiswa_yang_belum_terdaftar_di_kelas(): void
    {
        $this->masukSebagai(Role::MAHASISWA, Mahasiswa::factory()->create()->user);

        $this->get(route('mahasiswa.capaian'))
            ->assertOk()
            ->assertSee('Anda belum terdaftar di kelas mana pun.');
    }
}
