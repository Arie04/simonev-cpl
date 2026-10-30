# Changelog

Semua perubahan penting pada SIMONEV-CPL dicatat di file ini. Formatnya mengikuti
[Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan ditulis dalam bahasa Indonesia.
Perubahan yang belum selesai dicatat di `[Belum dirilis]`. Saat sebuah iterasi (bagian 11
spesifikasi) selesai, catatannya dipindah ke judul iterasi itu beserta tanggalnya.

## [Belum dirilis]

## [Iterasi 3: Pemetaan kurikulum dan Sub-CPMK] - 2026-10-30

### Ditambahkan

- Matriks Pemetaan Kurikulum untuk CPL–Profil Lulusan, CPL–Bahan Kajian, Bahan Kajian–Mata Kuliah
  (dengan bobot), dan CPMK–Mata Kuliah (dengan bobot). Matriks memberi peringatan jika total bobot
  CPMK sebuah MK bukan 100.
- Kelola Sub-CPMK per pasangan CPMK–MK, beserta rincian penilaiannya (metode, teknik, instrumen,
  kriteria) di halaman detail Sub-CPMK.
- Detail Mata Kuliah dengan ringkasan bobot CPMK dan Sub-CPMK, mirip Tabel 19 APTIKOM.
- Halaman Data Kurikulum untuk Koprodi (read-only) dengan tab dan pemilih kurikulum.
- Komponen tab, kotak peringatan, matriks pemetaan, dan ringkasan bobot mata kuliah.
- Data contoh pemetaan dan 25 Sub-CPMK yang semua bobotnya memenuhi aturan 5.1.
- 32 test baru (total 188).

### Diubah

- Pasangan CPMK–MK yang sudah punya Sub-CPMK tidak bisa dilepas dari matriks.
- Sub-CPMK yang sudah dipakai rencana asesmen tidak bisa dihapus atau dipindah pasangannya (KD-54).

## [Iterasi 2: Akun pengguna dan data master] - 2026-10-30

### Ditambahkan

- Kelola data master Program Studi, Kurikulum, Evaluasi Kurikulum, Profil Lulusan (beserta
  profesinya), Profesi, CPL, Bahan Kajian, Mata Kuliah, CPMK, dan Metode Penilaian, dengan
  pencarian, paginasi, dan dropdown Kurikulum.
- Validasi kode PL, CPL, BK, MK, dan CPMK unik per kurikulum tanpa membedakan huruf besar/kecil.
- Kelola Dosen dan Mahasiswa yang sekaligus membuat akun login.
- Menu Akun Pengguna untuk akun Admin/Koprodi, pengaturan peran, dan reset password.
- Beranda Admin berisi jumlah mahasiswa, dosen, mata kuliah, dan kelas, beserta pintasan menu.
- Pesan yang jelas saat menghapus data yang masih dipakai data lain.
- Komponen baru:
  - tabel responsif yang tampil sebagai kartu di layar kecil;
  - paginasi dan modal konfirmasi hapus;
  - select, textarea, kotak centang, badge, dan kartu statistik.
- Menu sidebar bergrup yang bisa dibuka-tutup.
- Data contoh:
  - program studi serta kurikulum 2021 dan 2026;
  - PL, profesi, CPL, BK, MK, CPMK, dan metode penilaian;
  - 3 dosen dan 23 mahasiswa contoh.
- 78 test baru (total 156).

### Diubah

- Spesifikasi diperbarui dengan hasil klarifikasi sebelum iterasi 2–10 (KD-35 sampai KD-49). Kelola
  Sub-CPMK pindah ke iterasi 3.
- Halaman utama Admin bukan lagi halaman sambutan sementara.

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
