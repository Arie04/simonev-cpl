<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Data simulasi dua tahun (KD-77): menambahkan riwayat perkuliahan 2024/2025 dan 2025/2026 di atas
 * data demo, seolah-olah sistem sudah dipakai sejak 2024/2025 Ganjil. Hasilnya bisa dilihat di
 * grafik Perkembangan CPL per Periode (Dashboard Mahasiswa) dan di Laporan Capaian per periode.
 *
 * Seeder ini opsional dan tidak dipanggil DatabaseSeeder, jadi data demo dan test tidak berubah.
 * Cara menjalankannya setelah data demo ada:
 *
 *     php artisan db:seed --class=SimulasiSeeder
 */
class SimulasiSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->dataDemoLengkap()) {
            throw new RuntimeException('Data demo belum ada atau tidak lengkap. Jalankan "php artisan migrate:fresh --seed" terlebih dahulu.');
        }

        // Satu transaksi: jika satu langkah gagal, tidak ada data simulasi yang tersimpan setengah jadi.
        DB::transaction(fn () => $this->call([
            SimulasiKurikulumSeeder::class,
            SimulasiPerkuliahanSeeder::class,
            SimulasiNilaiSeeder::class,
        ]));
    }

    /**
     * Simulasi dibangun di atas data demo, jadi penandanya harus ada: akun Dosen Demo, akun
     * Mahasiswa Demo, dan kelima kelas demo. Database berisi data lain tanpa data demo ditolak.
     */
    private function dataDemoLengkap(): bool
    {
        return Dosen::where('nidn', '9990000001')->exists()
            && Mahasiswa::where('nim', '9992300001')->exists()
            && count(PerkuliahanDemoSeeder::idKelasDemo()) === count(PerkuliahanDemoSeeder::DAFTAR_KELAS);
    }
}
