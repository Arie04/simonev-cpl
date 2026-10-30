# SIMONEV-CPL

**Sistem Monitoring dan Evaluasi Capaian Pembelajaran Lulusan** untuk Program Studi Informatika,
Fakultas Teknik, Universitas Khairun.

Dosen cukup menginput nilai per asesmen. Sistem lalu menghitung capaian Sub-CPMK, CPMK, dan CPL
mengikuti metode Panduan Kurikulum OBE APTIKOM Versi 2.0, sehingga setiap angka capaian bisa ditelusuri
balik ke asesmen sumbernya. Aplikasi ini adalah objek penelitian skripsi.

Dokumen terkait:

- Spesifikasi lengkap: [docs/spesifikasi.md](docs/spesifikasi.md)
- Keputusan desain: [docs/keputusan-desain.md](docs/keputusan-desain.md)
- Catatan penjelasan kode: [docs/catatan-kode/](docs/catatan-kode/)
- Riwayat perubahan: [CHANGELOG.md](CHANGELOG.md)

## Versi yang terpasang

Dicatat dari laptop pengembangan pada 30 Oktober 2026.

| Komponen | Versi |
|---|---|
| Ubuntu | 24.04.5 LTS |
| PHP | 8.3.6 |
| Laravel (`laravel/framework`) | 13.39.0 |
| MySQL | 8.0.46 |
| Composer | 2.7.1 |
| Node.js / npm | 24.22.2 / 11.19.1 |
| Vite | 8.3.5 |
| Tailwind CSS | 4.3.3 |
| Alpine.js | 3.17.5 |
| `blade-ui-kit/blade-heroicons` | 2.7.0 |
| `@fontsource/plus-jakarta-sans` | 5.3.0 |
| PHPUnit | 12.5.42 |

Chart.js 4 baru dipasang saat fitur laporan dibuat (iterasi 8).

## Persiapan database

Aplikasi memakai dua database MySQL dengan user yang sama: `simonev_cpl` untuk aplikasi dan
`simonev_cpl_test` khusus untuk test. Perintah berikut butuh `sudo`; ganti `ganti-password` dengan
password pilihan Anda.

```bash
sudo mysql -e "CREATE DATABASE simonev_cpl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER 'simonev'@'localhost' IDENTIFIED BY 'ganti-password'; GRANT ALL PRIVILEGES ON simonev_cpl.* TO 'simonev'@'localhost';"
sudo mysql -e "CREATE DATABASE simonev_cpl_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON simonev_cpl_test.* TO 'simonev'@'localhost';"
```

## Instalasi dan menjalankan di laptop

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# isi DB_PASSWORD (dan DB_* lain bila berbeda) di file .env
php artisan migrate --seed
```

Lalu jalankan dua perintah ini di dua terminal terpisah, dan buka <http://localhost:8000>:

```bash
npm run dev
php artisan serve
```

## Akun demo

Akun berikut dibuat oleh seeder dan hanya untuk pengembangan lokal. Password-nya adalah bagian email
sebelum `@` ditambah `123`.

| Peran | Email | Password | Bisa juga login dengan |
|---|---|---|---|
| Admin | `admin@simonev.test` | `admin123` | – |
| Koordinator Program Studi | `koprodi@simonev.test` | `koprodi123` | – |
| Dosen | `dosen@simonev.test` | `dosen123` | NIDN `9990000001` |
| Koprodi + Dosen | `koprodi.dosen@simonev.test` | `koprodi.dosen123` | NIDN `9990000002` |
| Mahasiswa | `mahasiswa@simonev.test` | `mahasiswa123` | NPM `9992300001` |

Akun Koprodi + Dosen diminta memilih peran setelah login dan bisa berganti peran lewat menu akun.

Selain lima akun di atas, data contoh berisi dosen dan mahasiswa tanpa email. Mereka login dengan
NIDN atau NPM:

| Data contoh | Login dengan | Password |
|---|---|---|
| 3 dosen | NIDN `9990000003` sampai `9990000005` | `dosen123` |
| 7 mahasiswa angkatan 2023 | NPM `9992300002` sampai `9992300008` | `mahasiswa123` |
| 8 mahasiswa angkatan 2024 | NPM `9992400001` sampai `9992400008` | `mahasiswa123` |
| 8 mahasiswa angkatan 2026 | NPM `9992600001` sampai `9992600008` | `mahasiswa123` |

Data kurikulum contoh terdiri atas Kurikulum 2021 dan Kurikulum OBE 2026. Beberapa kode sengaja sama
di keduanya (mis. CPL01 dan MK INF202). Setiap kurikulum sudah dipetakan (CPL–PL, CPL–BK, BK–MK, dan
CPMK–MK), dan setiap pasangan CPMK–MK sudah punya Sub-CPMK.

Data perkuliahan contoh terdiri atas periode 2025/2026 Genap dan 2026/2027 Ganjil, dengan lima kelas:

| Kelas | Periode | Dosen pengampu | Peserta |
|---|---|---|---|
| INF201 Struktur Data (2021) A | 2025/2026 Genap | Dosen Demo | angkatan 2024 |
| INF202 Basis Data (2021) A | 2026/2027 Ganjil | Dosen Demo | angkatan 2024 |
| INF301 Pemrograman Web (2021) A | 2026/2027 Ganjil | Koprodi Merangkap Dosen, Fadli Ramadhan | angkatan 2023 (termasuk Mahasiswa Demo) |
| INF101 Matematika Diskrit (2026) A | 2026/2027 Ganjil | Nurul Hidayah | angkatan 2026 dan Mahasiswa Demo |
| INF102 Algoritma dan Pemrograman (2026) A | 2026/2027 Ganjil | Dosen Demo, Ahmad Syarif | angkatan 2026 |

Semua data contoh adalah karangan, bukan data asli prodi.

## Mengembalikan data demo

```bash
php artisan migrate:fresh --seed
```

Perintah ini **menghapus seluruh isi** database `simonev_cpl`, lalu mengisinya lagi dengan data demo.

## Ambang batas capaian

Ambang Batas Kelulusan Mahasiswa dan Ambang Batas Ketercapaian diisi di `.env`
(`AMBANG_KELULUSAN_MAHASISWA` dan `AMBANG_KETERCAPAIAN`). Angka di `.env.example` hanya contoh APTIKOM
dan harus diganti sesuai ketetapan program studi. Setelah nilainya diubah, jalankan
`php artisan config:clear`.

## Menjalankan test dan merapikan kode

```bash
php artisan test            # menjalankan semua test
./vendor/bin/pint --test    # memeriksa format kode
./vendor/bin/pint           # merapikan format kode
```

Test memakai database `simonev_cpl_test` (lihat `phpunit.xml`) dan mengosongkannya setiap kali
dijalankan. Test akan berhenti sendiri jika database yang terpakai bukan database test, misalnya karena
config sedang di-cache. Jika itu terjadi, jalankan `php artisan config:clear`.

## Membuka dari HP di Wi-Fi yang sama

Untuk menunjukkan tampilan mobile, hentikan `npm run dev` jika sedang berjalan, lalu:

```bash
npm run build
php artisan serve --host=0.0.0.0 --port=8000
```

Cari IP laptop dengan `hostname -I`, lalu buka `http://<IP-laptop>:8000` di HP. Jika halaman tidak
terbuka, izinkan port 8000 di firewall dengan `sudo ufw allow 8000/tcp`.
