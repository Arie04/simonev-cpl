<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeAkademik;
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
    public function run(): void
    {
        $periodeLalu = PeriodeAkademik::firstOrCreate(['tahun_akademik' => '2025/2026', 'semester_periode' => PeriodeAkademik::GENAP]);
        $periodeAktif = PeriodeAkademik::firstOrCreate(['tahun_akademik' => '2026/2027', 'semester_periode' => PeriodeAkademik::GANJIL]);

        $daftarKelas = [
            // [tahun kurikulum, kode MK, periode, nama kelas, NIDN dosen, angkatan peserta, NPM tambahan]
            [2021, 'INF201', $periodeLalu, 'A', ['9990000001'], 2024, []],
            [2021, 'INF202', $periodeAktif, 'A', ['9990000001'], 2024, []],
            [2021, 'INF301', $periodeAktif, 'A', ['9990000002', '9990000003'], 2023, []],
            [2026, 'INF101', $periodeAktif, 'A', ['9990000004'], 2026, ['9992300001']],
            [2026, 'INF102', $periodeAktif, 'A', ['9990000001', '9990000005'], 2026, []],
        ];

        foreach ($daftarKelas as [$tahunKurikulum, $kodeMataKuliah, $periode, $namaKelas, $daftarNidn, $angkatan, $nimTambahan]) {
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
}
