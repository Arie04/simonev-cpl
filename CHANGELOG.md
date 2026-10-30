# Changelog

Semua perubahan penting pada SIMONEV-CPL dicatat di file ini. Formatnya mengikuti
[Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan ditulis dalam bahasa Indonesia.
Perubahan yang belum selesai dicatat di `[Belum dirilis]`. Saat sebuah iterasi (bagian 11
spesifikasi) selesai, catatannya dipindah ke judul iterasi itu beserta tanggalnya.

## [Belum dirilis]

## [Iterasi 1: Setup proyek, autentikasi, dan peran] - 2026-10-30

### Ditambahkan

- Proyek Laravel 13 dengan MySQL, bahasa Indonesia (`lang/id`), zona waktu WIT, Tailwind CSS v4,
  Alpine.js, font Plus Jakarta Sans, dan ikon Heroicons.
- Migrasi 29 tabel ERD dengan primary key UUID v7, aturan cascade/restrict, dan kode unik per kurikulum.
- 29 model Eloquent beserta relasinya (10 di antaranya model pivot ber-UUID) dan factory untuk test.
- Seeder empat peran dan lima akun demo.
- Login dengan email, NPM, atau NIDN, dengan batas 5 kali gagal per menit; logout.
- Pilih peran aktif setelah login dan ganti peran lewat menu akun untuk akun yang punya lebih dari
  satu peran.
- Middleware `peran` yang menolak (403) akses ke halaman di luar peran aktif.
- Layout utama responsif: top bar, sidebar yang menjadi drawer di layar kecil, menu akun, dan toast.
- Komponen Blade dasar: kartu, tombol, input, logo, dan halaman error.
- Halaman Profil dan Ubah Password.
- Halaman error 403, 404, 419, dan 500 berbahasa Indonesia.
- Halaman sambutan sementara sebagai halaman utama tiap peran.
- Konfigurasi ambang capaian (`config/capaian.php`) dan contohnya di `.env.example`.
- Pengaman di `tests/TestCase.php` agar test hanya berjalan di database test.
- 78 test fitur dan database: login, peran aktif, hak akses, profil, UUID, aturan hapus, dan seeder.
- README, catatan keputusan desain (`docs/keputusan-desain.md`), dan catatan penjelasan kode
  (`docs/catatan-kode/`).

### Diubah

- `docs/spesifikasi.md` diperbarui dengan hasil klarifikasi sebelum iterasi 1:
  - test memakai database MySQL;
  - tabel bawaan Laravel yang dipakai dan aturan cascade/restrict;
  - email boleh kosong untuk dosen dan mahasiswa;
  - keterangan kolom bahan kajian;
  - agregat CPL per periode, aturan MK yang diulang, dan label baris laporan;
  - penempatan halaman tambahan di urutan iterasi;
  - pola password akun demo.
