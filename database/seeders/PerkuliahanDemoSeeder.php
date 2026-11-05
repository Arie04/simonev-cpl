<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeAkademik;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

/**
 * Data contoh perkuliahan (iterasi 4, KD-43): dua periode, lima kelas dari kedua kurikulum,
 * dosen pengampu, dan peserta dari tiga angkatan.
 *
 * Mahasiswa Demo (angkatan 2023) mengikuti kelas kurikulum 2021 dan kurikulum 2026 sekaligus,
 * supaya halaman capaiannya memperlihatkan CPL yang dipisah per kurikulum (bagian 5.4).
 */
class PerkuliahanDemoSeeder extends Seeder
{
    /**
     * Kelas demo: [tahun kurikulum, kode MK, tahun akademik, semester, nama kelas, NIDN dosen,
     * angkatan peserta, NPM tambahan].
     */
    public const DAFTAR_KELAS = [
        [2021, 'INF201', '2025/2026', PeriodeAkademik::GENAP, 'A', ['9990000001'], 2024, []],
        [2021, 'INF202', '2026/2027', PeriodeAkademik::GANJIL, 'A', ['9990000001'], 2024, []],
        [2021, 'INF301', '2026/2027', PeriodeAkademik::GANJIL, 'A', ['9990000002', '9990000003'], 2023, []],
        [2026, 'INF101', '2026/2027', PeriodeAkademik::GANJIL, 'A', ['9990000004'], 2026, ['9992300001']],
        [2026, 'INF102', '2026/2027', PeriodeAkademik::GANJIL, 'A', ['9990000001', '9990000005'], 2026, []],
    ];

    public function run(): void
    {
        foreach (self::DAFTAR_KELAS as [$tahunKurikulum, $kodeMataKuliah, $tahunAkademik, $semester, $namaKelas, $daftarNidn, $angkatan, $nimTambahan]) {
            $periode = PeriodeAkademik::firstOrCreate(['tahun_akademik' => $tahunAkademik, 'semester_periode' => $semester]);

            $mataKuliah = MataKuliah::where('kode_mata_kuliah', $kodeMataKuliah)
                ->where('kurikulum_id', Kurikulum::where('tahun_berlaku', $tahunKurikulum)->value('kurikulum_id'))
                ->firstOrFail();

            $kelas = KelasKuliah::firstOrCreate([
                'mata_kuliah_id' => $mataKuliah->mata_kuliah_id,
                'periode_akademik_id' => $periode->periode_akademik_id,
                'nama_kelas' => $namaKelas,
            ]);

            $kelas->dosen()->sync(Dosen::whereIn('nidn', $daftarNidn)->pluck('dosen_id'));

            // syncWithoutDetaching: peserta lama (yang mungkin sudah punya nilai) tidak dihapus.
            $kelas->mahasiswa()->syncWithoutDetaching(
                Mahasiswa::where('angkatan', $angkatan)->orWhereIn('nim', $nimTambahan)->pluck('mahasiswa_id')
            );
        }
    }

    /**
     * Id kelas demo di atas. AsesmenDemoSeeder dan NilaiDemoSeeder hanya mengisi kelas-kelas ini,
     * jadi kelas lain (mis. kelas dari SimulasiSeeder) tidak berubah jika seeder demo dijalankan
     * ulang (KD-80).
     *
     * @return list<string>
     */
    public static function idKelasDemo(): array
    {
        return collect(self::DAFTAR_KELAS)
            ->map(function (array $data) {
                [$tahunKurikulum, $kodeMataKuliah, $tahunAkademik, $semester, $namaKelas] = $data;

                return KelasKuliah::where('nama_kelas', $namaKelas)
                    ->whereHas('mataKuliah', fn (Builder $query) => $query
                        ->where('kode_mata_kuliah', $kodeMataKuliah)
                        ->whereHas('kurikulum', fn (Builder $query) => $query->where('tahun_berlaku', $tahunKurikulum)))
                    ->whereHas('periodeAkademik', fn (Builder $query) => $query
                        ->where('tahun_akademik', $tahunAkademik)
                        ->where('semester_periode', $semester))
                    ->value('kelas_kuliah_id');
            })
            ->filter()
            ->values()
            ->all();
    }
}
