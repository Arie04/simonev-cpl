# SIMONEV-CPL

Sistem Monitoring dan Evaluasi Capaian Pembelajaran, aplikasi web Laravel untuk skripsi saya
(Program Studi Informatika, Universitas Khairun).
Spesifikasi lengkap ada di `docs/spesifikasi.md`. Dokumen itu adalah sumber kebenaran untuk
struktur data, rumus, desain, alur Git, dan urutan pengerjaan.

## Aturan setiap sesi

- Sebelum mengerjakan sebuah iterasi, baca `docs/spesifikasi.md`. Minimal baca bagian 11 (urutan
  iterasi) dan 14–16 (gaya kode, Git, catatan), ditambah bagian yang disentuh iterasi itu.
  Bagian 4 (basis data) dan 5 (perhitungan) wajib dibaca jika iterasinya menyentuh data atau nilai.
- Kerjakan **satu iterasi saja**, lalu berhenti dan beri ringkasan: apa yang selesai, cara
  mencobanya, hasil test, dan apa yang perlu saya putuskan.
- Iterasi berikutnya = iterasi pertama di bagian 11 yang belum tercatat di `CHANGELOG.md`.
- Jangan menambah/menghapus tabel, mengganti nama kolom, atau mengubah rumus tanpa bertanya dulu.
- Jika spesifikasi ambigu atau saling bertentangan, tanyakan dulu sebelum menulis kode.
- Teks antarmuka, komentar kode, catatan, dan pesan commit memakai bahasa Indonesia.
- Perintah yang butuh `sudo` jangan dijalankan; tuliskan perintahnya agar saya jalankan sendiri.

## Perintah

- Instal dependensi: `composer install` dan `npm install`
- Jalankan aplikasi: `php artisan serve` dan `npm run dev`
- Test: `php artisan test`
- Rapikan format: `./vendor/bin/pint`
- Reset data demo: `php artisan migrate:fresh --seed`

## Alur Git (detail di bagian 15)

- Satu branch per iterasi dari `main` terbaru, mis. `iterasi-01-setup`.
- Commit kecil dengan format Conventional Commits berbahasa Indonesia,
  mis. `feat(nilai): tambah form input nilai`.
- Sebelum membuat Pull Request:
  - semua test lulus dan Pint bersih;
  - `CHANGELOG.md` diperbarui;
  - catatan partisi ditulis di `docs/catatan-kode/`;
  - keputusan desain baru dicatat di `docs/keputusan-desain.md`.
- Buat Pull Request ke `main` dengan `gh pr create`. Jangan merge sendiri; saya yang merge
  setelah membaca catatannya.
- Jangan pernah commit `.env`, `vendor/`, `node_modules/`, atau data mahasiswa asli.

## Gaya kode (detail di bagian 14)

- Struktur Laravel standar: route → controller tipis → Form Request → model → Blade.
- Logika perhitungan capaian hanya ada di `app/Services/CapaianService.php`.
- Kode setingkat mahasiswa S1 yang rapi: tanpa repository pattern, interface, atau trik yang
  berlebihan. Saya harus bisa menjelaskan setiap bagiannya saat sidang.
- Istilah domain memakai bahasa Indonesia sesuai ERD (`MataKuliah`, `hitungCapaianCpl()`);
  istilah teknis Laravel tetap bahasa Inggris (`index`, `store`, `update`).

## Catatan khusus iterasi 1

- Repo ini sudah berisi `CLAUDE.md` dan `docs/`, jadi `composer create-project` langsung ke folder
  ini akan gagal karena foldernya tidak kosong. Buat proyek Laravel di folder sementara, lalu
  pindahkan isinya ke root repo tanpa menimpa `CLAUDE.md` dan `docs/`.
- Database MySQL dan user-nya sudah saya buat sendiri. Tanyakan nama database, user, dan
  password-nya saat mengisi `.env`.
