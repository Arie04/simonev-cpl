<?php

namespace Database\Seeders;

use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeAkademik;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Bagian perkuliahan dari data simulasi dua tahun (KD-78): tiga periode baru (2024/2025 Ganjil
 * sampai 2025/2026 Ganjil), mahasiswa angkatan 2022 dan 2025, serta 14 kelas kurikulum 2021.
 *
 * Jadwalnya disusun supaya menyambung dengan kelas demo: angkatan 2024 baru mengambil Struktur
 * Data di 2025/2026 Genap dan Basis Data di 2026/2027 Ganjil, sedangkan angkatan 2023 baru
 * mengambil Pemrograman Web di 2026/2027 Ganjil. Karena itu kelas-kelas tersebut tidak dibuat
 * ulang di sini untuk angkatan yang sama.
 */
class SimulasiPerkuliahanSeeder extends Seeder
{
    /** Mahasiswa baru: angkatan 2022 (semester 5 saat simulasi dimulai) dan 2025 (masuk di tengah simulasi). */
    private const MAHASISWA_BARU = [
        2022 => ['Yusuf Maulana', 'Zahra Aulia', 'Andi Firmansyah', 'Bunga Pertiwi', 'Candra Wijaya', 'Dewi Anggraeni', 'Eko Prasetyo', 'Fitri Handayani'],
        2025 => ['Galih Saputra', 'Hana Pratiwi', 'Ilham Ramadhan', 'Jihan Putri', 'Kevin Mahendra', 'Laila Sari', 'Miftahul Huda', 'Nabila Azzahra'],
    ];

    /**
     * Kelas simulasi kurikulum 2021, semuanya bernama "A":
     * [kode MK, tahun akademik, semester, NIDN dosen, angkatan peserta, NPM yang mengulang].
     */
    public const DAFTAR_KELAS = [
        // Tahun pertama simulasi: 2024/2025.
        ['INF103', '2024/2025', PeriodeAkademik::GANJIL, ['9990000005'], 2024, []],
        ['INF201', '2024/2025', PeriodeAkademik::GANJIL, ['9990000001'], 2023, []],
        ['INF202', '2024/2025', PeriodeAkademik::GANJIL, ['9990000001'], 2023, []],
        ['INF301', '2024/2025', PeriodeAkademik::GANJIL, ['9990000002', '9990000003'], 2022, []],
        ['INF302', '2024/2025', PeriodeAkademik::GANJIL, ['9990000002'], 2022, []],
        ['INF105', '2024/2025', PeriodeAkademik::GENAP, ['9990000004'], 2024, []],
        ['INF204', '2024/2025', PeriodeAkademik::GENAP, ['9990000001'], 2023, []],
        ['INF303', '2024/2025', PeriodeAkademik::GENAP, ['9990000003', '9990000004'], 2022, []],
        // Tahun kedua: 2025/2026. Dua mahasiswa 2024 mengulang INF103 dan satu mahasiswa 2022
        // mengulang INF302, supaya terlihat aturan kelas terbaru yang sudah bernilai (bagian 5.4).
        ['INF103', '2025/2026', PeriodeAkademik::GANJIL, ['9990000005', '9990000001'], 2025, ['9992400007', '9992400008']],
        ['INF203', '2025/2026', PeriodeAkademik::GANJIL, ['9990000003'], 2024, []],
        ['INF302', '2025/2026', PeriodeAkademik::GANJIL, ['9990000002'], 2023, ['9992200008']],
        ['INF105', '2025/2026', PeriodeAkademik::GENAP, ['9990000004'], 2025, []],
        ['INF204', '2025/2026', PeriodeAkademik::GENAP, ['9990000001'], 2024, []],
        ['INF303', '2025/2026', PeriodeAkademik::GENAP, ['9990000003', '9990000004'], 2023, []],
    ];

    public function run(): void
    {
        foreach (self::MAHASISWA_BARU as $angkatan => $daftarNama) {
            foreach ($daftarNama as $indeks => $nama) {
                // NPM contoh: 999 + dua digit angkatan + nomor urut, sama seperti data demo.
                $this->buatMahasiswa('999'.substr((string) $angkatan, 2).sprintf('%05d', $indeks + 1), $nama, $angkatan);
            }
        }

        foreach (self::DAFTAR_KELAS as [$kodeMataKuliah, $tahunAkademik, $semester, $daftarNidn, $angkatan, $nimMengulang]) {
            $periode = PeriodeAkademik::firstOrCreate(['tahun_akademik' => $tahunAkademik, 'semester_periode' => $semester]);

            $kelas = KelasKuliah::firstOrCreate([
                'mata_kuliah_id' => self::mataKuliah($kodeMataKuliah)->mata_kuliah_id,
                'periode_akademik_id' => $periode->periode_akademik_id,
                'nama_kelas' => 'A',
            ]);

            $kelas->dosen()->sync(Dosen::whereIn('nidn', $daftarNidn)->pluck('dosen_id'));

            // Hanya mahasiswa contoh (NPM diawali 999), supaya mahasiswa lain yang mungkin ada di
            // database tidak ikut masuk kelas simulasi.
            $kelas->mahasiswa()->syncWithoutDetaching(
                Mahasiswa::where('nim', 'like', '999%')
                    ->where(fn ($query) => $query->where('angkatan', $angkatan)->orWhereIn('nim', $nimMengulang))
                    ->pluck('mahasiswa_id')
            );
        }
    }

    /**
     * Kelas simulasi untuk satu MK pada satu periode. Dipakai SimulasiNilaiSeeder.
     */
    public static function cariKelas(string $kodeMataKuliah, string $tahunAkademik, string $semester): KelasKuliah
    {
        return KelasKuliah::where('mata_kuliah_id', self::mataKuliah($kodeMataKuliah)->mata_kuliah_id)
            ->where('nama_kelas', 'A')
            ->whereHas('periodeAkademik', fn ($query) => $query->where('tahun_akademik', $tahunAkademik)->where('semester_periode', $semester))
            ->firstOrFail();
    }

    private static function mataKuliah(string $kodeMataKuliah): MataKuliah
    {
        return MataKuliah::where('kode_mata_kuliah', $kodeMataKuliah)
            ->where('kurikulum_id', Kurikulum::where('tahun_berlaku', 2021)->value('kurikulum_id'))
            ->firstOrFail();
    }

    /**
     * Mahasiswa contoh tanpa email, login dengan NPM dan password mahasiswa123 (seperti data demo).
     * Jika sudah ada (seeder dijalankan ulang), data itu yang diperbarui.
     */
    private function buatMahasiswa(string $nim, string $nama, int $angkatan): void
    {
        $user = Mahasiswa::where('nim', $nim)->first()?->user ?? new User;
        $user->fill(['nama' => $nama, 'email' => null, 'password' => 'mahasiswa123'])->save();
        $user->roles()->sync([Role::ambil(Role::MAHASISWA)->role_id]);

        Mahasiswa::updateOrCreate(['nim' => $nim], ['user_id' => $user->user_id, 'nama' => $nama, 'angkatan' => $angkatan]);
    }
}
