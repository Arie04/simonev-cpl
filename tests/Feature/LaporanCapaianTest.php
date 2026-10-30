<?php

namespace Tests\Feature;

use App\Models\Cpl;
use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeAkademik;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MembangunDataCapaian;
use Tests\TestCase;

/**
 * Dashboard Koprodi dan Laporan Capaian (bagian 7.6.2 dan 7.6.9 spesifikasi): skenario uji 12
 * (dashboard), 13 (filter laporan), dan 14 (laporan dosen).
 */
class LaporanCapaianTest extends TestCase
{
    use MembangunDataCapaian;
    use RefreshDatabase;

    private Kurikulum $kurikulum;

    private MataKuliah $mataKuliah;

    private KelasKuliah $kelas;

    private PeriodeAkademik $periodeLama;

    /** @var list<Mahasiswa> */
    private array $mahasiswa = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['capaian.ambang_kelulusan_mahasiswa' => 50.01, 'capaian.ambang_ketercapaian' => 85]);

        // CPL01 diukur UTS, CPL02 diukur UAS, CPL03 belum dipetakan ke CPMK mana pun.
        $this->kurikulum = Kurikulum::factory()->create(['tahun_berlaku' => 2026, 'nama_kurikulum' => 'Kurikulum OBE 2026']);
        $this->mataKuliah = $this->buatMataKuliah($this->kurikulum, 'INF101', [
            'CPMK011' => ['cpl' => 'CPL01', 'bobot' => 50, 'sub' => ['S1' => 50]],
            'CPMK021' => ['cpl' => 'CPL02', 'bobot' => 50, 'sub' => ['S2' => 50]],
        ]);
        Cpl::factory()->for($this->kurikulum)->create(['kode_cpl' => 'CPL03']);
        $asesmen = [
            'UTS' => ['bobot' => 50, 'maks' => 100, 'kontribusi' => ['S1' => 50]],
            'UAS' => ['bobot' => 50, 'maks' => 100, 'kontribusi' => ['S2' => 50]],
        ];

        $this->periodeLama = PeriodeAkademik::factory()->create(['tahun_akademik' => '2025/2026', 'semester_periode' => 'Genap']);
        $kelasLama = $this->buatKelas($this->mataKuliah, $asesmen, $this->periodeLama);
        $this->kelas = $this->buatKelas($this->mataKuliah, $asesmen, PeriodeAkademik::factory()->create(['tahun_akademik' => '2026/2027', 'semester_periode' => 'Ganjil']));

        // Semua mahasiswa pernah gagal di periode lama, lalu mengulang di periode baru.
        // Periode baru: CPL01 4/4 lulus (Tercapai); CPL02 3/4 lulus = 75% (Tidak Tercapai).
        $nilaiBaru = [[80, 80], [70, 70], [90, 90], [60, 30]];

        foreach ([2025, 2025, 2026, 2026] as $urutan => $angkatan) {
            $mahasiswa = Mahasiswa::factory()->create(['angkatan' => $angkatan, 'nim' => '99900'.$urutan, 'nama' => "Mahasiswa {$urutan}"]);
            $this->beriNilai($kelasLama, $mahasiswa, ['UTS' => 40, 'UAS' => 40]);
            $this->beriNilai($this->kelas, $mahasiswa, ['UTS' => $nilaiBaru[$urutan][0], 'UAS' => $nilaiBaru[$urutan][1]]);
            $this->mahasiswa[] = $mahasiswa;
        }
    }

    public function test_koprodi_membuka_dashboard_dan_ringkasan_capaian_cpl_tampil(): void
    {
        $this->masukSebagai(Role::KOPRODI);

        $this->get(route('koprodi.dashboard'))
            ->assertOk()
            ->assertSee('Ringkasan Capaian CPL')
            ->assertSeeInOrder(['Mahasiswa', '4'])
            ->assertSeeInOrder(['CPL', '3'])
            ->assertSee('Periode 2026/2027 Ganjil')
            ->assertSee('≥ 85% mahasiswa lulus')
            // Donut: 1 Tercapai (CPL01), 1 Tidak Tercapai (CPL02), 1 Belum ada nilai (CPL03).
            ->assertSee('[1,1,1]', false);
    }

    public function test_ringkasan_dashboard_mengikuti_filter_angkatan(): void
    {
        $this->masukSebagai(Role::KOPRODI);

        // Angkatan 2025 lulus semua CPL01 dan CPL02.
        $this->get(route('koprodi.dashboard', ['angkatan' => 2025]))
            ->assertSee('CPL kumulatif semua periode, angkatan 2025.')
            ->assertSee('[2,0,1]', false);
    }

    public function test_dashboard_memberi_pemberitahuan_jika_ambang_belum_ditetapkan(): void
    {
        config(['capaian.ambang_ketercapaian' => null]);
        $this->masukSebagai(Role::KOPRODI);

        $this->get(route('koprodi.dashboard'))
            ->assertSee('Ambang ketercapaian belum ditetapkan program studi.')
            ->assertDontSee('grafikDonut');
    }

    public function test_laporan_cpl_menampilkan_status_dan_ambang_yang_berlaku(): void
    {
        $this->masukSebagai(Role::KOPRODI);

        $this->get(route('koprodi.laporan'))
            ->assertOk()
            ->assertSee('Ambang Batas Kelulusan Mahasiswa (AM): <strong>50,01</strong>', false)
            ->assertSee('Ambang Batas Ketercapaian (AK): <strong>85,00%</strong>', false)
            ->assertSee('CPL kumulatif semua periode.')
            ->assertSeeInOrder(['CPL01', '100,00 (4/4)', 'Tercapai', 'CPL02', '75,00 (3/4)', 'Tidak Tercapai', 'CPL03', 'Belum ada nilai']);
    }

    public function test_filter_periode_hanya_memakai_kelas_pada_periode_itu(): void
    {
        $this->masukSebagai(Role::KOPRODI);

        // Periode lama: semua mahasiswa mendapat 40, jadi tidak ada yang lulus.
        $this->get(route('koprodi.laporan', ['periode' => $this->periodeLama->periode_akademik_id]))
            ->assertSee('CPL dihitung dari kelas pada periode ini saja.')
            ->assertSeeInOrder(['CPL01', '40,00', '0,00 (0/4)', 'Tidak Tercapai']);
    }

    public function test_filter_mahasiswa_hanya_menghitung_mahasiswa_itu(): void
    {
        $this->masukSebagai(Role::KOPRODI);

        $this->get(route('koprodi.laporan', ['mahasiswa' => $this->mahasiswa[3]->mahasiswa_id]))
            ->assertSeeInOrder(['CPL01', '60,00', '100,00 (1/1)', 'CPL02', '30,00', '0,00 (0/1)']);
    }

    public function test_tab_cpmk_berlabel_pasangan_dengan_kode_mk_dan_diurutkan_per_cpmk(): void
    {
        // CPMK011 juga dipakai di INF102, jadi ada dua baris CPMK011 yang letaknya berdekatan (KD-25).
        $mataKuliahKedua = $this->buatMataKuliah($this->kurikulum, 'INF102', ['CPMK011' => ['cpl' => 'CPL01', 'bobot' => 100, 'sub' => ['S9' => 100]]]);
        $kelasKedua = $this->buatKelas($mataKuliahKedua, ['UAS' => ['bobot' => 100, 'maks' => 100, 'kontribusi' => ['S9' => 100]]]);
        $this->beriNilai($kelasKedua, $this->mahasiswa[0], ['UAS' => 90]);
        $this->masukSebagai(Role::KOPRODI);

        $this->get(route('koprodi.laporan', ['tab' => 'cpmk']))
            ->assertSeeInOrder(['CPMK011 · INF101', 'CPMK011 · INF102', 'CPMK021 · INF101']);

        // Jika satu MK dipilih, label cukup kode CPMK.
        $this->get(route('koprodi.laporan', ['tab' => 'cpmk', 'mata_kuliah' => $this->mataKuliah->mata_kuliah_id]))
            ->assertDontSee('CPMK011 · INF101')
            ->assertSeeInOrder(['CPMK011', 'CPMK021'])
            ->assertDontSee('CPMK011 · INF102');
    }

    public function test_tab_sub_cpmk_dan_kartu_ringkasan(): void
    {
        $this->masukSebagai(Role::KOPRODI);

        $this->get(route('koprodi.laporan', ['tab' => 'sub-cpmk']))
            ->assertSeeInOrder(['S1 · INF101', 'S2 · INF101'])
            ->assertSeeInOrder(['Rata-rata Capaian', 'Capaian Tertinggi', 'S1 · INF101', 'Capaian Terendah', 'S2 · INF101', 'Jumlah Item', '2']);
    }

    public function test_laporan_tanpa_ambang_menampilkan_ambang_belum_ditetapkan(): void
    {
        config(['capaian.ambang_kelulusan_mahasiswa' => null, 'capaian.ambang_ketercapaian' => null]);
        $this->masukSebagai(Role::KOPRODI);

        $this->get(route('koprodi.laporan'))
            ->assertSee('Ambang Batas Kelulusan Mahasiswa (AM): <strong>Belum ditetapkan</strong>', false)
            ->assertSee('Ambang belum ditetapkan')
            ->assertSee('Grafik Rata-rata Capaian');
    }

    public function test_laporan_dosen_hanya_berisi_kelas_yang_diampu(): void
    {
        $dosen = Dosen::factory()->create();
        $this->kelas->dosen()->attach($dosen);

        // Kelas MK lain yang tidak diampu dosen ini.
        $mataKuliahLain = $this->buatMataKuliah($this->kurikulum, 'INF999', ['CPMK099' => ['cpl' => 'CPL01', 'bobot' => 100, 'sub' => ['S99' => 100]]]);
        $kelasLain = $this->buatKelas($mataKuliahLain, ['UAS' => ['bobot' => 100, 'maks' => 100, 'kontribusi' => ['S99' => 100]]]);
        $this->beriNilai($kelasLain, $this->mahasiswa[0], ['UAS' => 10]);

        $this->masukSebagai(Role::DOSEN, $dosen->user);

        $this->get(route('dosen.laporan', ['tab' => 'cpmk']))
            ->assertOk()
            ->assertSee('CPMK011 · INF101')
            ->assertDontSee('INF999');

        // Mengetik id kelas lain di alamat tidak membuka datanya.
        $this->get(route('dosen.laporan', ['tab' => 'cpmk', 'mata_kuliah' => $mataKuliahLain->mata_kuliah_id, 'kelas' => $kelasLain->kelas_kuliah_id]))
            ->assertDontSee('CPMK099');

        // CPL di laporan dosen hanya dihitung dari kelas yang ia ampu (KD-46): CPL01 tetap 4/4 lulus.
        $this->get(route('dosen.laporan'))->assertSeeInOrder(['CPL01', '100,00 (4/4)']);
    }

    public function test_laporan_dosen_tanpa_kelas_menampilkan_pesan(): void
    {
        $this->masukSebagai(Role::DOSEN, Dosen::factory()->create()->user);

        $this->get(route('dosen.laporan'))->assertOk()->assertSee('Anda belum mengampu kelas.');
    }

    public function test_laporan_dan_dashboard_dibatasi_per_peran(): void
    {
        foreach ([Role::ADMIN, Role::MAHASISWA, Role::DOSEN] as $kodeRole) {
            $this->masukSebagai($kodeRole);
            $this->get(route('koprodi.laporan'))->assertForbidden();
            $this->get(route('koprodi.dashboard'))->assertForbidden();
        }

        foreach ([Role::ADMIN, Role::MAHASISWA, Role::KOPRODI] as $kodeRole) {
            $this->masukSebagai($kodeRole);
            $this->get(route('dosen.laporan'))->assertForbidden();
        }
    }
}
