<?php

namespace Database\Seeders;

use App\Models\KelasKuliah;
use Illuminate\Database\Seeder;

/**
 * Data contoh nilai asesmen (iterasi 6, KD-43). Nilainya dibuat dengan rumus tetap (bukan acak)
 * supaya hasil dashboard dan laporan selalu sama setiap kali seeder dijalankan.
 *
 * Sebagian nilai sengaja dikosongkan untuk memperlihatkan aturan nilai belum diinput (bagian 5.4):
 * UAS Basis Data kurikulum 2021 belum dinilai sama sekali, dan dua mahasiswa belum punya nilai
 * satu asesmen.
 */
class NilaiDemoSeeder extends Seeder
{
    /** Kemampuan dasar mahasiswa (skala 0–100), dipakai bergiliran sesuai urutan NPM. */
    private const KEMAMPUAN = [88, 82, 76, 71, 64, 58, 52, 47, 91, 67];

    public function run(): void
    {
        $daftarKelas = KelasKuliah::with(['mataKuliah.kurikulum', 'rencanaAsesmen', 'pesertaKelas.mahasiswa'])->get();

        foreach ($daftarKelas as $kelas) {
            $daftarPeserta = $kelas->pesertaKelas->sortBy('mahasiswa.nim')->values();
            $daftarAsesmen = $kelas->rencanaAsesmen->sortBy('tanggal_asesmen')->values();

            foreach ($daftarAsesmen as $urutanAsesmen => $asesmen) {
                if ($this->belumDinilai($kelas, $asesmen->nama_asesmen)) {
                    continue;
                }

                $nilaiPerPeserta = [];

                foreach ($daftarPeserta as $urutanPeserta => $peserta) {
                    // Dua peserta pertama kelas INF102 belum punya nilai UTS (nilai sebagian kosong).
                    if ($kelas->mataKuliah->kode_mata_kuliah === 'INF102' && $asesmen->nama_asesmen === 'UTS' && $urutanPeserta < 2) {
                        $nilaiPerPeserta[$peserta->peserta_kelas_id] = null;

                        continue;
                    }

                    $persen = $this->nilaiPersen($urutanPeserta, $urutanAsesmen);
                    $nilaiPerPeserta[$peserta->peserta_kelas_id] = round($persen * $asesmen->nilai_maksimum / 100, 1);
                }

                $asesmen->simpanNilai($nilaiPerPeserta);
            }
        }
    }

    /**
     * Nilai dalam persen: kemampuan dasar mahasiswa ditambah variasi kecil per asesmen (−5 sampai +5).
     */
    private function nilaiPersen(int $urutanPeserta, int $urutanAsesmen): float
    {
        $kemampuan = self::KEMAMPUAN[$urutanPeserta % count(self::KEMAMPUAN)];
        $variasi = (($urutanAsesmen * 7 + $urutanPeserta * 3) % 11) - 5;

        return max(0, min(100, $kemampuan + $variasi));
    }

    private function belumDinilai(KelasKuliah $kelas, string $namaAsesmen): bool
    {
        return $kelas->mataKuliah->kode_mata_kuliah === 'INF202'
            && $kelas->mataKuliah->kurikulum->tahun_berlaku === 2021
            && $namaAsesmen === 'UAS';
    }
}
