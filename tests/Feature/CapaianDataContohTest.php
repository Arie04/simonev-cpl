<?php

namespace Tests\Feature;

use App\Models\Cpl;
use App\Models\KelasKuliah;
use App\Models\Mahasiswa;
use App\Models\PesertaKelas;
use App\Services\CapaianService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skenario uji 11 (bagian 12 spesifikasi): jika nilai asesmen tersedia lengkap, capaian
 * Sub-CPMK, CPMK, dan CPL terhitung. Memakai data contoh dari seeder.
 */
class CapaianDataContohTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        config(['capaian.ambang_kelulusan_mahasiswa' => 50.01, 'capaian.ambang_ketercapaian' => 85]);
    }

    public function test_kelas_dengan_nilai_lengkap_menghasilkan_capaian_semua_item(): void
    {
        $kelas = KelasKuliah::whereHas('mataKuliah', fn ($query) => $query->where('kode_mata_kuliah', 'INF201'))->firstOrFail();
        $capaian = new CapaianService;

        foreach (PesertaKelas::with(CapaianService::RELASI_PESERTA)->where('kelas_kuliah_id', $kelas->kelas_kuliah_id)->get() as $peserta) {
            $hasil = $capaian->hitungCapaianPeserta($peserta);

            // Struktur Data: 3 Sub-CPMK pada 2 pasangan CPMK–MK, semua asesmen sudah dinilai.
            $this->assertCount(3, $hasil['sub_cpmk']);
            $this->assertCount(2, $hasil['cpmk']);
            $this->assertNotNull($hasil['nilai_akhir']);

            // Rumus 5: Σ(CCPMK × BCPMK) / 100 = nilai akhir MK jika bobot konsisten.
            $this->assertEqualsWithDelta(
                $hasil['nilai_akhir'],
                collect($hasil['cpmk'])->sum(fn (array $cpmk) => $cpmk['nilai'] * $cpmk['bobot']) / 100,
                0.0001,
            );
        }
    }

    public function test_capaian_cpl_mahasiswa_demo_terpisah_per_kurikulum(): void
    {
        $mahasiswa = Mahasiswa::where('nim', '9992300001')->firstOrFail();
        $capaian = new CapaianService;

        $cpl = $capaian->hitungCapaianCpl($capaian->pilihKelasPerMataKuliah(
            PesertaKelas::with(CapaianService::RELASI_PESERTA)->where('mahasiswa_id', $mahasiswa->mahasiswa_id)->get()
        ));

        $kurikulumCpl = Cpl::whereIn('cpl_id', array_keys($cpl))->with('kurikulum')->get()
            ->pluck('kurikulum.tahun_berlaku')->unique()->sort()->values()->all();

        $this->assertSame([2021, 2026], $kurikulumCpl);

        foreach ($cpl as $hasil) {
            $this->assertGreaterThanOrEqual(0, $hasil['nilai']);
            $this->assertLessThanOrEqual(100, $hasil['nilai']);
        }
    }

    public function test_agregat_data_contoh_berisi_status_lulus_dan_tercapai(): void
    {
        $capaian = new CapaianService;
        $perItem = $capaian->capaianPerItem(PesertaKelas::with(CapaianService::RELASI_PESERTA)->get());

        $this->assertNotEmpty($perItem['cpl']);

        foreach ($perItem['cpl'] as $daftarCapaian) {
            $rekap = $capaian->rekap($daftarCapaian);

            $this->assertGreaterThan(0, $rekap['jumlah_mahasiswa']);
            $this->assertNotNull($rekap['persen_lulus']);
            $this->assertIsBool($rekap['tercapai']);
        }
    }
}
