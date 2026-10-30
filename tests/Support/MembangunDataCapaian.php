<?php

namespace Tests\Support;

use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\NilaiAsesmen;
use App\Models\PemetaanCpmkMk;
use App\Models\PeriodeAkademik;
use App\Models\PesertaKelas;
use App\Models\RencanaAsesmen;
use App\Models\SubCpmk;
use App\Services\CapaianService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pembantu test untuk menyusun data perhitungan capaian dengan ringkas: mata kuliah beserta CPL,
 * CPMK, dan Sub-CPMK; kelas beserta rencana asesmen; lalu nilai mahasiswa.
 */
trait MembangunDataCapaian
{
    /**
     * Membuat mata kuliah beserta CPL, CPMK, dan Sub-CPMK-nya di satu kurikulum.
     * Bentuk $daftarCpmk: kode CPMK => [kode CPL, bobot CPMK, [kode Sub-CPMK => bobot target]].
     *
     * @param  array<string, array{cpl: string, bobot: float|int, sub: array<string, float|int>}>  $daftarCpmk
     */
    protected function buatMataKuliah(Kurikulum $kurikulum, string $kodeMataKuliah, array $daftarCpmk): MataKuliah
    {
        $mataKuliah = MataKuliah::factory()->for($kurikulum)->create(['kode_mata_kuliah' => $kodeMataKuliah]);

        foreach ($daftarCpmk as $kodeCpmk => $data) {
            $cpl = Cpl::firstOrCreate(
                ['kurikulum_id' => $kurikulum->kurikulum_id, 'kode_cpl' => $data['cpl']],
                ['kategori_cpl' => 'Pengetahuan', 'deskripsi_cpl' => 'Deskripsi '.$data['cpl']],
            );
            // CPMK yang sama boleh dipakai di beberapa MK (mis. CPMK063 di MK04 dan MK34).
            $cpmk = Cpmk::firstOrCreate(
                ['cpl_id' => $cpl->cpl_id, 'kode_cpmk' => $kodeCpmk],
                ['deskripsi_cpmk' => 'Deskripsi '.$kodeCpmk],
            );
            $pemetaan = PemetaanCpmkMk::create([
                'cpmk_id' => $cpmk->cpmk_id,
                'mata_kuliah_id' => $mataKuliah->mata_kuliah_id,
                'bobot_cpmk' => $data['bobot'],
            ]);

            foreach ($data['sub'] as $kodeSubCpmk => $bobotSubCpmk) {
                SubCpmk::factory()->for($pemetaan)->create(['kode_sub_cpmk' => $kodeSubCpmk, 'bobot_sub_cpmk' => $bobotSubCpmk]);
            }
        }

        return $mataKuliah;
    }

    /**
     * Membuat kelas beserta rencana asesmennya.
     * Bentuk $daftarAsesmen: nama asesmen => [bobot, nilai maksimum, [kode Sub-CPMK => bobot kontribusi]].
     *
     * @param  array<string, array{bobot: float|int, maks: float|int, kontribusi: array<string, float|int>}>  $daftarAsesmen
     */
    protected function buatKelas(MataKuliah $mataKuliah, array $daftarAsesmen, ?PeriodeAkademik $periode = null): KelasKuliah
    {
        $kelas = KelasKuliah::factory()
            ->for($mataKuliah)
            ->for($periode ?? PeriodeAkademik::factory()->create(['tahun_akademik' => '2026/2027', 'semester_periode' => 'Ganjil']))
            ->create();

        foreach (array_keys($daftarAsesmen) as $urutan => $namaAsesmen) {
            $data = $daftarAsesmen[$namaAsesmen];
            $rencanaAsesmen = RencanaAsesmen::factory()->for($kelas)->create([
                'nama_asesmen' => $namaAsesmen,
                'bobot_asesmen' => $data['bobot'],
                'nilai_maksimum' => $data['maks'],
                // Tanggal berurutan supaya urutan asesmen di halaman sama dengan urutan penulisan.
                'tanggal_asesmen' => now()->setDate(2026, 9, 1)->addWeeks($urutan)->toDateString(),
            ]);

            $kontribusi = [];

            foreach ($data['kontribusi'] as $kodeSubCpmk => $bobot) {
                $kontribusi[$this->subCpmk($mataKuliah, $kodeSubCpmk)->sub_cpmk_id] = $bobot;
            }

            $rencanaAsesmen->petakanSubCpmk($kontribusi);
        }

        return $kelas;
    }

    /**
     * Mendaftarkan mahasiswa ke kelas (jika belum) lalu memberi nilai per asesmen.
     *
     * @param  array<string, float|int>  $nilaiPerAsesmen  nama asesmen => nilai
     */
    protected function beriNilai(KelasKuliah $kelas, Mahasiswa $mahasiswa, array $nilaiPerAsesmen): PesertaKelas
    {
        $peserta = PesertaKelas::firstOrCreate([
            'kelas_kuliah_id' => $kelas->kelas_kuliah_id,
            'mahasiswa_id' => $mahasiswa->mahasiswa_id,
        ]);

        foreach ($nilaiPerAsesmen as $namaAsesmen => $nilai) {
            NilaiAsesmen::create([
                'rencana_asesmen_id' => $kelas->rencanaAsesmen()->where('nama_asesmen', $namaAsesmen)->value('rencana_asesmen_id'),
                'peserta_kelas_id' => $peserta->peserta_kelas_id,
                'nilai' => $nilai,
            ]);
        }

        return $peserta;
    }

    protected function subCpmk(MataKuliah $mataKuliah, string $kodeSubCpmk): SubCpmk
    {
        return SubCpmk::where('kode_sub_cpmk', $kodeSubCpmk)
            ->whereHas('pemetaanCpmkMk', fn ($query) => $query->where('mata_kuliah_id', $mataKuliah->mata_kuliah_id))
            ->firstOrFail();
    }

    protected function pemetaan(MataKuliah $mataKuliah, string $kodeCpmk): PemetaanCpmkMk
    {
        return PemetaanCpmkMk::where('mata_kuliah_id', $mataKuliah->mata_kuliah_id)
            ->whereHas('cpmk', fn ($query) => $query->where('kode_cpmk', $kodeCpmk))
            ->firstOrFail();
    }

    protected function cpl(Kurikulum $kurikulum, string $kodeCpl): Cpl
    {
        return Cpl::where('kurikulum_id', $kurikulum->kurikulum_id)->where('kode_cpl', $kodeCpl)->firstOrFail();
    }

    /**
     * Peserta kelas yang dimuat ulang beserta relasi yang dibutuhkan CapaianService.
     *
     * @return Collection<int, PesertaKelas>
     */
    protected function muatPeserta(?callable $saring = null): Collection
    {
        $query = PesertaKelas::with(CapaianService::RELASI_PESERTA);

        if ($saring !== null) {
            $saring($query);
        }

        return $query->get();
    }
}
