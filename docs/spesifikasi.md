# Spesifikasi SIMONEV-CPL: Sistem Monitoring & Evaluasi Capaian Pembelajaran (OBE)

Kamu adalah pengembang Laravel senior. Bantu saya membangun aplikasi web **Sistem Informasi Monitoring dan Evaluasi Capaian Pembelajaran Mahasiswa** untuk Program Studi Informatika, Fakultas Teknik, Universitas Khairun. Nama aplikasinya **SIMONEV-CPL** (Sistem Monitoring dan Evaluasi Capaian Pembelajaran Lulusan), dan nama repositorinya `simonev-cpl`.

Aplikasi ini adalah objek penelitian skripsi saya. Struktur data, peran pengguna, dan rumus perhitungan **harus mengikuti spesifikasi di bawah**. Jangan menambah/menghapus tabel, mengganti nama kolom, atau mengubah rumus tanpa bertanya dulu. Sebelum mulai menulis kode, baca seluruh spesifikasi ini dan tanyakan hal yang menurutmu ambigu. Kerjakan di repositori GitHub saya (bagian 15), ikuti gaya kode di bagian 14, dan tulis catatan penjelasan kode untuk saya (bagian 16).

---

## 1. Konteks

- Prodi menerapkan **Outcome-Based Education (OBE)** dengan capaian bertingkat:
  **CPL** (Capaian Pembelajaran Lulusan, tingkat prodi) → **CPMK** (Capaian Pembelajaran Mata Kuliah) → **Sub-CPMK** (tahap pembelajaran) → **asesmen** (kuis, tugas, UTS, UAS, proyek, praktikum).
- Dasar perhitungan capaian: **Panduan Kurikulum Berbasis OBE/KKNI/SKKNI APTIKOM, Program Studi Sarjana Informatika/Ilmu Komputer, Versi 2.0**, Bab J "Asesmen Pembelajaran" (Tabel 18a, 19, 20, Tabel K) dan bagian Penilaian pada contoh RPS di Lampiran 8. Sistem ini menambahkan tingkat Sub-CPMK di antara asesmen dan CPMK tanpa mengubah hasil akhirnya (lihat bagian 5).
- Saat ini data kurikulum, pemetaan, dan nilai tersebar di dokumen dan spreadsheet, sehingga harus diolah ulang setiap kali prodi ingin tahu ketercapaian CPMK/CPL.
- Sistem dipakai untuk **lebih dari satu kurikulum**: kurikulum yang berlaku sekarang dan kurikulum berikutnya (arahan dosen pembimbing). Dalam satu periode, kelas dari kedua kurikulum bisa berjalan bersamaan.
- Prinsip inti: **dosen hanya menginput nilai per asesmen**. Sistem yang menghitung capaian Sub-CPMK → CPMK → CPL, sehingga setiap angka capaian bisa ditelusuri balik ke asesmen sumbernya.

## 2. Teknologi

- **PHP 8.3** (bawaan Ubuntu 24.04), **Laravel 13** (`laravel/framework ^13.0`, minimal PHP 8.3), **MySQL 8.0**, Composer 2, dan Node.js versi LTS untuk Vite. Lingkungan pengembangan: Ubuntu 24.04 dengan `php artisan serve` dan `npm run dev`. Catat versi pasti yang terpasang di README.
- Tampilan: **Blade + Tailwind CSS v4 + Alpine.js 3**, grafik dengan **Chart.js 4**, mobile-first (detail desain di bagian 7).
- Autentikasi session bawaan Laravel yang dibuat sendiri secara sederhana (controller login + `Auth::attempt`), tanpa starter kit, karena Breeze sudah tidak dikembangkan dan starter kit resmi Laravel memakai React, Vue, atau Livewire. Otorisasi dengan middleware peran + Policy/Gate.
- Lokalisasi: `APP_TIMEZONE=Asia/Jayapura` (WIT, zona waktu Ternate), `APP_LOCALE=id`, `APP_FAKER_LOCALE=id_ID`, dan pesan validasi berbahasa Indonesia di `lang/id/`.
- Pengujian: PHPUnit (unit test untuk perhitungan, feature test untuk skenario uji di bagian 12). Test berjalan di database MySQL terpisah `simonev_cpl_test`, bukan SQLite, supaya perilakunya sama dengan aplikasi (mis. collation MySQL menganggap kode `CPL01` dan `cpl01` sama).
- Bahasa antarmuka: Bahasa Indonesia.

## 3. Peran Pengguna & Hak Akses

| Peran (kode_role) | Hak akses |
|---|---|
| **ADMIN** | Kelola akun & peran pengguna (KF-02); kelola data master kurikulum, termasuk evaluasi kurikulum (KF-03), serta data Dosen & Mahasiswa; kelola pemetaan kurikulum (KF-04); kelola periode akademik & kelas kuliah (KF-05); tentukan dosen pengampu & peserta kelas (KF-06). |
| **KOPRODI** (Koordinator Program Studi) | Read-only seluruh prodi: dashboard monitoring (KF-12), lihat data kurikulum termasuk evaluasi kurikulum, laporan capaian prodi Sub-CPMK/CPMK/CPL (KF-13) dengan filter (KF-16). |
| **DOSEN** | Hanya untuk kelas tempat ia terdaftar di `dosen_kelas`: lihat kelas yang diampu; lihat CPL, CPMK, Sub-CPMK mata kuliahnya (KF-07); kelola rencana asesmen (KF-08); petakan asesmen ke Sub-CPMK beserta bobot kontribusi (KF-09); input/ubah nilai (KF-10); laporan capaian kelas/MK yang diampu, yaitu Sub-CPMK, CPMK, dan kontribusinya ke CPL (KF-14), dengan filter (KF-16). |
| **MAHASISWA** | Read-only, hanya data miliknya: nilai asesmen serta capaian Sub-CPMK, CPMK, dan CPL (KF-15). |

- Satu user boleh punya lebih dari satu peran (tabel `user_roles`), misalnya Koordinator Prodi yang juga Dosen. Setelah login: jika peran tunggal, langsung ke halaman utama peran itu; jika lebih dari satu, user memilih peran aktif dan bisa berganti peran lewat menu akun di header.
- Pembatasan akses wajib dicek di server (middleware/Policy), bukan sekadar menyembunyikan menu.

## 4. Struktur Basis Data (dari ERD)

Aturan umum:
- Nama tabel dan kolom persis seperti di bawah (snake_case huruf kecil). PK bernama `<tabel>_id`, jadi set `$table` dan `$primaryKey` di tiap model Eloquent.
- **Semua PK memakai UUID (GUID), bukan angka auto-increment**, termasuk tabel pivot dan `users`:
  - Migrasi: `$table->uuid('<tabel>_id')->primary()`. FK: `$table->foreignUuid('<kolom>_id')->constrained('<tabel_induk>', '<tabel_induk>_id')`.
  - Model: trait `HasUuids` (UUID terurut berdasarkan waktu, supaya index tetap efisien). Trait ini sudah mengatur `$incrementing = false` dan `$keyType = 'string'`.
  - Tabel pivot yang punya PK sendiri (mis. `user_roles`, `pemetaan_cpmk_mk`, `peserta_kelas`, `rencana_asesmen_sub_cpmk`) harus tetap terisi UUID saat dibuat lewat relasi, misalnya dengan model Pivot ber-`HasUuids` dan `->using(...)`. Buat test untuk ini.
  - Ubah juga migrasi bawaan Laravel yang merujuk ke users: `sessions.user_id` harus `foreignUuid`, bukan `foreignId`.
  - Tabel bawaan Laravel yang dipakai hanya `sessions` (session disimpan di database; `user_id` diberi index tanpa foreign key constraint, seperti bawaan Laravel), serta `cache` dan `cache_locks` (dipakai pembatas percobaan login). `password_reset_tokens`, `jobs`, `job_batches`, dan `failed_jobs` tidak dibuat karena tidak ada reset password lewat email dan tidak ada antrean (`QUEUE_CONNECTION=sync`).
  - Validasi ID dari request dengan aturan `uuid` dan `exists`.
  - Jangan memakai urutan id untuk menentukan data "terbaru". Kurikulum terbaru = `tahun_berlaku` terbesar; periode terbaru = `tahun_akademik` terbesar, lalu Genap setelah Ganjil.
- Tambahkan `timestamps()` di semua tabel dan `remember_token` di `users`.
- `UQ(a, b)` = unique gabungan. Semua FK memakai foreign key constraint.
- Karena sistem menangani lebih dari satu kurikulum, kode-kode kurikulum unik **per kurikulum** (atau per induknya), bukan unik global. Di ERD hanya tanda UQ yang berubah; tidak ada tabel atau kolom baru.
- Penghapusan: tolak (restrict) dan tampilkan pesan jika data masih dipakai. Cascade hanya untuk baris pivot/pemetaan, dengan aturan: baris pivot ikut terhapus jika *pemiliknya* dihapus, tetapi data yang *dirujuk* tidak bisa dihapus selama masih dipakai.
  - Cascade dari kedua sisi (pemetaan kurikulum): `pemetaan_cpl_pl`, `pemetaan_cpl_bk`, `pemetaan_bk_mk`, `pemetaan_cpmk_mk`, `profil_lulusan_profesi`.
  - Cascade hanya dari sisi pemilik, sedangkan sisi yang dirujuk restrict: `user_roles` (pemilik `users`), `dosen_kelas` dan `peserta_kelas` (pemilik `kelas_kuliah`), `rencana_asesmen_sub_cpmk` (pemilik `rencana_asesmen`), `sub_cpmk_penilaian` (pemilik `sub_cpmk`).
  - Semua FK lain restrict. Akibatnya Sub-CPMK yang sudah dipakai rencana asesmen, dosen yang masih mengampu kelas, mahasiswa yang masih terdaftar di kelas, peserta yang sudah punya nilai, dan pemetaan CPMK–MK yang sudah punya Sub-CPMK tidak bisa dihapus. Dengan begitu bobot kontribusi dan nilai kelas lama tidak hilang diam-diam (lihat 5.1).
- Tipe yang disarankan: PK/FK `uuid` (CHAR(36) di MySQL), kolom bobot `decimal(5,2)` (kecuali `bobot_min` dan `bobot_max` di `bahan_kajian`, lihat B), `nilai` dan `nilai_maksimum` `decimal(6,2)`, deskripsi panjang `text`, tanggal `date`.

### A. Pengguna
- `users`: user_id, nama, email (UQ, boleh kosong), password. Email boleh kosong untuk akun dosen dan mahasiswa karena mereka bisa login dengan NIDN/NPM. Akun yang tidak punya data dosen atau mahasiswa (mis. Admin) wajib punya email; ini divalidasi di aplikasi.
- `roles`: role_id, kode_role (UQ: ADMIN, KOPRODI, DOSEN, MAHASISWA), nama_role
- `user_roles`: user_role_id, user_id (FK), role_id (FK), UQ(user_id, role_id)
- `dosen`: dosen_id, user_id (FK, UQ), nidn (UQ), nama
- `mahasiswa`: mahasiswa_id, user_id (FK, UQ), nim (UQ), nama, angkatan

### B. Kurikulum
- `program_studi`: program_studi_id, nama_perguruan_tinggi, nama_fakultas, nama_program_studi, izin_pendirian, peringkat_akreditasi, jenjang_pendidikan, gelar_lulusan, visi_keilmuan, misi_program_studi
- `kurikulum`: kurikulum_id, program_studi_id (FK), nama_kurikulum, tahun_berlaku (UQ)
- `evaluasi_kurikulum`: evaluasi_kurikulum_id, kurikulum_id (FK), tahap_evaluasi, kinerja_mutu, standar_kinerja_mutu, kesenjangan, tindak_lanjut
- `profil_lulusan`: profil_lulusan_id, kurikulum_id (FK), kode_pl, deskripsi_pl, unsur, sumber, UQ(kurikulum_id, kode_pl)
- `profesi`: profesi_id, kurikulum_id (FK), nama_profesi
- `profil_lulusan_profesi`: profil_lulusan_profesi_id, profil_lulusan_id (FK), profesi_id (FK), UQ(profil_lulusan_id, profesi_id)
- `cpl`: cpl_id, kurikulum_id (FK), kode_cpl, kategori_cpl, deskripsi_cpl, UQ(kurikulum_id, kode_cpl)
- `bahan_kajian`: bahan_kajian_id, kurikulum_id (FK), kode_bk, nama_bahan_kajian, kategori_bk, bobot_min, bobot_max, cs_core, ka_core, referensi, UQ(kurikulum_id, kode_bk). Isi kolomnya mengikuti Tabel 4 buku kurikulum APTIKOM:
  - `bobot_min` dan `bobot_max`: bobot tingkat kepentingan (*importance*) BK menurut CC2020, bukan SKS dan bukan persen. Bilangan bulat kecil (di Tabel 4 nilainya 2 sampai 6), tipe `unsignedTinyInteger`, boleh kosong. Validasi: bilangan bulat ≥ 0 dan `bobot_min` ≤ `bobot_max`. Jangan dibatasi maksimal 5, karena BK22 Metodologi bernilai 2–6.
  - `cs_core` dan `ka_core`: jumlah jam pembahasan menurut CS2023 (*CS Core hours* dan *KA Core hours*), bilangan bulat (mis. 17 dan 14), tipe `unsignedSmallInteger`, boleh kosong. Null dan 0 berbeda: null berarti BK itu tidak bersumber dari CS2023 (mis. BK03, BK04, BK14 yang bersumber dari CC2020), sedangkan 0 berarti memang 0 jam.
  - `referensi`: teks sumber, mis. "CS2023" atau "CC2020".
  - `kategori_bk`: kelompok di Tabel 4, yaitu "Kompetensi Utama", "Softskill", atau "Umum". Disimpan sebagai teks; form menampilkan ketiganya sebagai saran, tetapi boleh diisi lain.
  - Kolom-kolom ini hanya data dokumentasi kurikulum dan tidak dipakai dalam perhitungan capaian.
- `mata_kuliah`: mata_kuliah_id, kurikulum_id (FK), kode_mata_kuliah, nama_mata_kuliah, semester, jumlah_sks, jenis_mata_kuliah, level_bloom_mk, keterangan, UQ(kurikulum_id, kode_mata_kuliah)
- `cpmk`: cpmk_id, cpl_id (FK), kode_cpmk, deskripsi_cpmk, UQ(cpl_id, kode_cpmk). Di aplikasi, kode CPMK juga divalidasi unik dalam satu kurikulum (lewat `cpl.kurikulum_id`).
- `sub_cpmk`: sub_cpmk_id, pemetaan_cpmk_mk_id (FK), kode_sub_cpmk, deskripsi_sub_cpmk, bobot_sub_cpmk, materi, pertemuan_mulai, pertemuan_selesai, catatan, UQ(pemetaan_cpmk_mk_id, kode_sub_cpmk). Kode yang sama boleh dipakai di pasangan CPMK–MK lain, seperti di Tabel 15 APTIKOM (Sub-CPMK0811 dipakai di MK04, MKP02, dan MK08).
- `metode_penilaian`: metode_penilaian_id, kode_metode (UQ), nama_metode
- `sub_cpmk_penilaian`: sub_cpmk_penilaian_id, sub_cpmk_id (FK), metode_penilaian_id (FK), teknik_penilaian, instrumen, kriteria, UQ(sub_cpmk_id, metode_penilaian_id)

### C. Pemetaan Kurikulum
- `pemetaan_cpl_pl`: pemetaan_cpl_pl_id, cpl_id (FK), profil_lulusan_id (FK), UQ(cpl_id, profil_lulusan_id)
- `pemetaan_cpl_bk`: pemetaan_cpl_bk_id, cpl_id (FK), bahan_kajian_id (FK), UQ(cpl_id, bahan_kajian_id)
- `pemetaan_bk_mk`: pemetaan_bk_mk_id, bahan_kajian_id (FK), mata_kuliah_id (FK), bobot_bk_mk, UQ(bahan_kajian_id, mata_kuliah_id). `bobot_bk_mk` adalah porsi BK dalam MK dalam persen (0–100), hanya dokumentasi kurikulum, dan jumlahnya per MK tidak diperiksa.
- `pemetaan_cpmk_mk`: pemetaan_cpmk_mk_id, cpmk_id (FK), mata_kuliah_id (FK), bobot_cpmk, UQ(cpmk_id, mata_kuliah_id)

### D. Pelaksanaan Perkuliahan
- `periode_akademik`: periode_akademik_id, tahun_akademik (mis. "2026/2027"), semester_periode (Ganjil/Genap)
- `kelas_kuliah`: kelas_kuliah_id, mata_kuliah_id (FK), periode_akademik_id (FK), nama_kelas
- `dosen_kelas`: dosen_kelas_id, kelas_kuliah_id (FK), dosen_id (FK), UQ(kelas_kuliah_id, dosen_id)
- `peserta_kelas`: peserta_kelas_id, kelas_kuliah_id (FK), mahasiswa_id (FK), UQ(kelas_kuliah_id, mahasiswa_id)

### E. Asesmen & Nilai
- `rencana_asesmen`: rencana_asesmen_id, kelas_kuliah_id (FK), metode_penilaian_id (FK), nama_asesmen, bobot_asesmen, nilai_maksimum, tanggal_asesmen
- `rencana_asesmen_sub_cpmk`: rencana_asesmen_sub_cpmk_id, rencana_asesmen_id (FK), sub_cpmk_id (FK), bobot_kontribusi, UQ(rencana_asesmen_id, sub_cpmk_id)
- `nilai_asesmen`: nilai_asesmen_id, rencana_asesmen_id (FK), peserta_kelas_id (FK), nilai, catatan, UQ(rencana_asesmen_id, peserta_kelas_id)

### Relasi kunci
- `program_studi` 1–N `kurikulum`; `kurikulum` 1–N `profil_lulusan`, `profesi`, `cpl`, `bahan_kajian`, `mata_kuliah`, `evaluasi_kurikulum`.
- `cpl` 1–N `cpmk` (setiap CPMK milik tepat satu CPL).
- `cpmk` N–N `mata_kuliah` lewat `pemetaan_cpmk_mk` (dengan `bobot_cpmk`).
- `pemetaan_cpmk_mk` 1–N `sub_cpmk`. **Sub-CPMK menempel ke pasangan CPMK–MK, bukan ke CPMK langsung**, karena CPMK yang sama bisa dipakai di beberapa MK dengan rumusan Sub-CPMK, materi, dan bobot berbeda.
- `mata_kuliah` 1–N `kelas_kuliah`; `periode_akademik` 1–N `kelas_kuliah`.
- `kelas_kuliah` N–N `dosen` (lewat `dosen_kelas`) dan N–N `mahasiswa` (lewat `peserta_kelas`).
- `kelas_kuliah` 1–N `rencana_asesmen`; `rencana_asesmen` N–N `sub_cpmk` lewat `rencana_asesmen_sub_cpmk` (dengan `bobot_kontribusi`).
- `nilai_asesmen` unik per (asesmen, peserta kelas).
- `mahasiswa` tidak terhubung langsung ke `kurikulum`. CPL yang dihitung untuk seorang mahasiswa mengikuti kurikulum dari MK yang ia ikuti.

## 5. Logika Perhitungan Capaian (KF-11, inti sistem)

Metode mengikuti contoh perhitungan APTIKOM: capaian = **jumlah skor yang diperoleh / jumlah skor maksimal × 100%** (Tabel K). Di sini metode itu ditulis sebagai rata-rata tertimbang bertingkat (asesmen → Sub-CPMK → CPMK → CPL), dan hasilnya identik dengan cara APTIKOM.

### 5.1 Satu acuan bobot: persen dari nilai akhir mata kuliah

Semua bobot dinyatakan dalam **% nilai akhir MK**, sama seperti matriks "Bobot per Bentuk Penilaian" di RPS APTIKOM (baris = CPMK/Sub-CPMK, kolom = asesmen, total semua sel = 100).

| Kolom | Arti | Peran |
|---|---|---|
| `rencana_asesmen_sub_cpmk.bobot_kontribusi` | Bagian asesmen ini yang mengukur Sub-CPMK ini, dalam % nilai akhir MK (satu sel matriks RPS di kelas itu) | **Dasar perhitungan.** Semua bobot di 5.2 diturunkan dari kolom ini |
| `rencana_asesmen.bobot_asesmen` | Bobot asesmen terhadap nilai akhir MK (total kolom matriks) | Harus = Σ `bobot_kontribusi` asesmen itu; Σ per kelas = 100 |
| `sub_cpmk.bobot_sub_cpmk` | **Target** bobot Sub-CPMK dalam MK menurut RPS/kurikulum | Validasi dan tampilan. Di setiap kelas, Σ `bobot_kontribusi` ke Sub-CPMK ini seharusnya sama dengan nilai ini |
| `pemetaan_cpmk_mk.bobot_cpmk` | **Target** bobot CPMK dalam MK, yaitu "skor maks CPMK" pada Tabel 19 APTIKOM | Validasi dan tampilan. Seharusnya = Σ `bobot_sub_cpmk` milik pemetaan itu; Σ per MK = 100 |

- Perhitungan **selalu** memakai bobot yang benar-benar dipakai di kelas (dari `rencana_asesmen_sub_cpmk`), bukan bobot target di kurikulum. Jadi kalau bobot di kurikulum direvisi, hasil kelas yang sudah lewat **tidak berubah**. Bobot target hanya memunculkan peringatan bila rencana asesmen kelas tidak sesuai.

- Total bobot yang terkumpul pada satu CPL boleh kurang atau lebih dari 100 (Tabel 20 APTIKOM), karena dinormalisasi oleh penyebut rumus.
- SKS **tidak** dipakai sebagai bobot. Setiap MK berbobot total 100, sesuai APTIKOM.

### 5.2 Rumus (per mahasiswa, yaitu per baris `peserta_kelas`)

```
1) Normalisasi nilai asesmen ke skala 0–100
   Na = (Nm / Nmaks) × 100
   Nm = nilai_asesmen.nilai, Nmaks = rencana_asesmen.nilai_maksimum

2) Capaian Sub-CPMK
   CSub = Σ(Na_i × bobot_kontribusi_i) / Σ bobot_kontribusi_i
   i = asesmen di kelas tersebut yang dipetakan ke Sub-CPMK ini

3) Capaian CPMK (untuk satu pasangan CPMK × MK, di kelas yang diikuti mahasiswa)
   CCPMK  = Σ(CSub_j × BSub_j) / Σ BSub_j
   j      = Sub-CPMK milik pemetaan_cpmk_mk tersebut yang diases di kelas itu
   BSub_j = Σ bobot_kontribusi ke Sub-CPMK j di kelas itu
            (jika rencana asesmen sesuai RPS, nilainya sama dengan sub_cpmk.bobot_sub_cpmk)

4) Capaian CPL
   CCPL    = Σ(CCPMK_k × BCPMK_k) / Σ BCPMK_k
   k       = setiap PASANGAN CPMK–MK yang CPMK-nya milik CPL ini dan MK-nya sudah diikuti mahasiswa.
             Jika satu CPMK dipakai di dua MK, CPMK itu dihitung dua kali dengan bobotnya masing-masing
             (contoh APTIKOM: CPMK063 di MK04 berbobot 20 dan di MK34 berbobot 30).
   BCPMK_k = Σ BSub_j untuk pasangan k di kelas yang diikuti mahasiswa, yaitu skor maks CPMK yang
             benar-benar diases (jika sesuai RPS, sama dengan pemetaan_cpmk_mk.bobot_cpmk)

5) Nilai akhir MK (hanya untuk cek konsistensi, bukan fitur KHS)
   NA_MK = Σ(Na_i × bobot_asesmen_i) / 100
   Jika bobot konsisten: Σ(CCPMK_k × BCPMK_k) / 100 = NA_MK   (lihat Tabel G APTIKOM)
```

Dengan BSub dan BCPMK seperti di atas, rumus 3 dan 4 menghasilkan angka yang sama persis dengan cara APTIKOM (Σ skor / Σ skor maks).

### 5.3 Status ketercapaian dan agregat (catatan RPS APTIKOM, Lampiran 8)

- **Ambang Batas Kelulusan Mahasiswa (AM)**: nilai minimal yang harus dicapai seorang mahasiswa. Seorang mahasiswa dinyatakan **Lulus** pada suatu Sub-CPMK/CPMK/CPL jika capaiannya ≥ AM.
- Status Lulus dan Tercapai dibandingkan memakai angka yang sudah dibulatkan 2 desimal, yaitu angka yang tampil di layar. Dengan begitu tidak ada capaian yang tampil "50,01" tetapi berstatus Belum Lulus. Perhitungan berantai (Sub-CPMK → CPMK → CPL) tetap memakai presisi penuh.
- **Ambang Batas Ketercapaian (AK)**: persentase minimal mahasiswa yang Lulus. Sebuah Sub-CPMK/CPMK (di satu kelas atau MK) atau CPL (pada satu angkatan atau periode) dinyatakan **Tercapai** jika % mahasiswa Lulus ≥ AK.
- Agregat **bukan** rata-rata nilai. Agregat adalah persentase jumlah mahasiswa yang lulus, sesuai APTIKOM: "persen ketercapaian jumlah mahasiswa yang lulus dalam setiap CPL Prodi". Rata-rata capaian tetap ditampilkan sebagai informasi pendukung.
- AM dan AK **disimpan di file `.env`**, bukan di database, sehingga ERD tidak berubah:
  ```
  # Ambang Batas Kelulusan Mahasiswa, skala 0–100
  AMBANG_KELULUSAN_MAHASISWA=50.01
  # Ambang Batas Ketercapaian, persen 0–100 (85 berarti 85%)
  AMBANG_KETERCAPAIAN=85
  ```
- Buat `config/capaian.php` yang membaca kedua nilai itu dengan `env(...)` dan default `null`. Kode aplikasi hanya membacanya lewat `config('capaian.ambang_kelulusan_mahasiswa')` dan `config('capaian.ambang_ketercapaian')`. Jangan memanggil `env()` di luar file config (nilainya menjadi `null` saat config di-cache), dan jangan menulis angka ambang di kode.
- Ambil ambang hanya lewat satu method, misalnya `CapaianService::ambang()`. Dengan begitu, kalau nanti ambang dipindah ke database, cukup method itu yang diubah.
- Nilainya ditetapkan prodi dan diisi oleh pengelola server. Angka di atas hanya contoh APTIKOM. Pakai titik sebagai pemisah desimal. Setelah `.env` diubah, jalankan `php artisan config:clear`, atau `php artisan config:cache` jika config di-cache, agar nilai baru terbaca.
- Ambang berlaku global untuk seluruh perhitungan: semua kurikulum, Sub-CPMK, CPMK, dan CPL. Angka capaian kelas lama tidak berubah saat bobot kurikulum direvisi (5.1), tetapi status Lulus/Tercapai selalu memakai ambang `.env` yang berlaku saat ini.
- Jika `AMBANG_KELULUSAN_MAHASISWA` kosong atau tidak valid, status Lulus dan % mahasiswa Lulus tidak dihitung. Jika `AMBANG_KETERCAPAIAN` kosong atau tidak valid, status Tercapai tidak dihitung. Di kedua kasus, tampilkan "Ambang belum ditetapkan" di tempat status. Nilai capaian dan rata-rata tetap ditampilkan.
- CPL per mahasiswa bersifat kumulatif dari MK yang sudah diikuti, dan bisa dipantau setiap tingkat sampai lulus. Agregat CPL selalu dihitung dalam satu kurikulum, dan bisa dilihat per angkatan (APTIKOM: "secara agregat setiap angkatan") dan per periode. Jika filter periode dipilih, CPL dihitung hanya dari kelas pada periode itu. Jika "Semua periode", CPL dihitung kumulatif dari semua kelas. Laporan menampilkan keterangan kecil "CPL dihitung dari kelas pada periode ini saja" atau "CPL kumulatif semua periode". Dashboard Koprodi selalu kumulatif (semua periode), mengikuti filter kurikulum dan angkatan.

### 5.4 Aturan teknis

- **Nilai belum diinput** diabaikan: penyebut hanya memakai bobot yang punya nilai. Jika tidak ada nilai sama sekali, hasilnya `null` dan ditampilkan "Belum ada nilai", bukan 0. Mahasiswa dengan hasil `null` tidak ikut dihitung dalam persentase agregat. Karena itu BSub dan BCPMK di 5.2 dihitung per mahasiswa dari bobot kontribusi asesmen yang sudah punya nilai. Hasilnya tetap sama dengan Σ skor / Σ skor maks APTIKOM pada asesmen yang sudah dinilai.
- Jika mahasiswa punya lebih dari satu kelas untuk MK yang sama (mengulang), capaian CPL memakai kelas terbaru tempat mahasiswa itu sudah punya minimal satu nilai asesmen. Jika kelas terbaru belum punya nilai sama sekali, pakai kelas sebelumnya yang sudah punya nilai. Begitu kelas terbaru punya nilai, kelas itu yang dipakai (nilai parsial tetap dihitung sesuai aturan nilai kosong di atas). Tujuannya agar capaian CPL tidak tiba-tiba hilang di awal semester saat mahasiswa mengulang. Aturan yang sama dipakai di tab CPMK dan Sub-CPMK laporan, sehingga setiap mahasiswa dihitung sekali per MK.
- CPL dari kurikulum berbeda tidak pernah digabung, walaupun kodenya sama (mis. CPL01 kurikulum 2021 dan CPL01 kurikulum 2026). Semua perhitungan dan pengelompokan memakai id, bukan kode. Jika seorang mahasiswa mengikuti MK dari dua kurikulum (masa transisi), capaian CPL-nya ditampilkan terpisah per kurikulum.
- Hitung dengan presisi penuh, tampilkan 2 desimal.
- Implementasikan dalam satu service class (mis. `App\Services\CapaianService`) yang dipakai dashboard, laporan, dan halaman mahasiswa. Jangan menduplikasi rumus di controller/view. Hitung on-the-fly dengan eager loading; cache boleh ditambahkan belakangan.

### 5.5 Contoh uji (wajib dijadikan unit test)

**Uji 1: asesmen → Sub-CPMK → CPMK → nilai akhir MK.**

MK Matematika Diskrit, kelas A.
- CPMK-X (milik CPL-01) dengan `bobot_cpmk` 60, terdiri dari Sub-CPMK S1 (`bobot_sub_cpmk` 25) dan S2 (35).
- CPMK-Y (milik CPL-02) dengan `bobot_cpmk` 40, terdiri dari Sub-CPMK S3 (40).

| Asesmen | bobot_asesmen | Nilai maks | Nilai mhs | Na | bobot_kontribusi |
|---|---|---|---|---|---|
| Kuis 1 | 10 | 50 | 40 | 80 | S1: 10 |
| Tugas 1 | 20 | 100 | 85 | 85 | S2: 20 |
| UTS | 30 | 100 | 70 | 70 | S1: 15, S2: 15 |
| UAS | 40 | 100 | 90 | 90 | S3: 40 |

Pengecekan bobot: S1 = 10 + 15 = 25 ✓, S2 = 20 + 15 = 35 ✓, S3 = 40 ✓, CPMK-X = 25 + 35 = 60 ✓, total = 100 ✓. Karena kelas ini sesuai target, BSub = 25, 35, 40 dan BCPMK = 60, 40.

- S1 = (80×10 + 70×15) / 25 = **74.00**
- S2 = (85×20 + 70×15) / 35 = **78.57**
- S3 = **90.00**
- CPMK-X = (74×25 + 78.5714×35) / 60 = **76.67**; CPMK-Y = **90.00**
- Nilai akhir MK = (80×10 + 85×20 + 70×30 + 90×40) / 100 = **82.00** = (76.6667×60 + 90×40) / 100 ✓

**Uji 2: CPMK → CPL, memakai data Tabel K APTIKOM.**

Bobot (skor maks, dari Tabel 19):
- MK04: CPMK061 = 15, CPMK062 = 15, CPMK063 = 20 (ketiganya CPL06); CPMK081 = 15, CPMK082 = 15, CPMK083 = 20 (ketiganya CPL08).
- MK34: CPMK063 = 30 (CPL06); CPMK071 = 40, CPMK072 = 30 (keduanya CPL07).

Capaian CPMK = skor / skor maks × 100. Skor mahasiswa per kolom dengan urutan seperti di atas:

| Mahasiswa | MK04: 061, 062, 063, 081, 082, 083 | MK34: 063, 071, 072 | CPL06 | CPL08 | CPL07 |
|---|---|---|---|---|---|
| Mhs 1 | 10, 10, 15, 10, 10, 15 | 25, 35, 25 | **75.00** | **70.00** | **85.71** |
| Mhs 2 | 15, 15, 15, 10, 15, 15 | 20, 35, 25 | **81.25** | **80.00** | **85.71** |
| Mhs 3 | 15, 14, 17, 13, 12, 16 | 25, 36, 28 | **88.75** | **82.00** | **91.43** |
| Mhs 4 | 15, 13, 18, 14, 13, 18 | 28, 35, 28 | **92.50** | **90.00** | **90.00** |

Angka ini sama dengan kolom "Capaian" di Tabel K APTIKOM (75%, 81%, 89%, 93%, dst.).

**Uji 3: agregat.** Di test, set `config(['capaian.ambang_kelulusan_mahasiswa' => 50.01, 'capaian.ambang_ketercapaian' => 85])`. Satu kelas berisi 50 mahasiswa untuk satu CPMK: 30 mahasiswa > 50,01, 5 mahasiswa = 50,01, dan 15 mahasiswa < 50,01. Yang Lulus = 35/50 = 70%. Karena 70% < 85%, statusnya **Tidak Tercapai** (contoh pada catatan RPS APTIKOM).

**Uji 4: ambang belum diisi.** Data sama dengan Uji 3, tetapi kedua nilai config ambang di-set `null`. Nilai capaian tetap terhitung, sedangkan status dan % Lulus menampilkan "Ambang belum ditetapkan".

**Uji 5: revisi bobot kurikulum tidak mengubah hasil lama.** Setelah Uji 1, ubah target `bobot_sub_cpmk` S1 menjadi 30 dan S2 menjadi 30. Capaian kelas A tetap sama (S1 74.00, S2 78.57, CPMK-X 76.67, nilai akhir 82.00), dan halaman Rencana Asesmen kelas A menampilkan peringatan bahwa bobot kelas tidak sesuai target.

**Uji 6: dua kurikulum.** Kurikulum 2021 dan 2026 sama-sama punya CPL berkode `CPL01` dan satu MK dengan kode yang sama. Keduanya bisa disimpan, tetapi kode ganda di dalam kurikulum yang sama ditolak. Mahasiswa yang hanya mengikuti MK kurikulum 2026 hanya mendapat capaian CPL kurikulum 2026; CPL01 kurikulum 2021 tidak ikut terhitung.

Tambahkan juga test untuk kasus nilai belum lengkap (sebagian asesmen kosong, dan semuanya kosong).

## 6. Aturan Validasi

- Login salah → tolak dan tampilkan pesan kesalahan.
- Tidak ada registrasi publik; semua akun dibuat Admin dengan password awal yang diisi Admin. Tidak ada reset password lewat email: halaman login menampilkan "Lupa password? Hubungi Admin program studi", dan Admin mereset lewat menu Akun Pengguna.
- Batasi percobaan login: maksimal 5 kali gagal per menit untuk kombinasi identitas login dan IP, dengan pesan berbahasa Indonesia.
- Membuka fitur di luar peran → 403.
- Dosen hanya bisa membuka/mengubah kelas yang ia ampu; mahasiswa hanya datanya sendiri (cek lewat Policy).
- Nilai harus numerik dan 0 ≤ nilai ≤ `nilai_maksimum` asesmen.
- `nilai_maksimum` > 0; semua bobot ≥ 0. Bobot dalam persen (`bobot_bk_mk`, `bobot_cpmk`, `bobot_sub_cpmk`, `bobot_asesmen`, `bobot_kontribusi`) juga ≤ 100. `nilai_maksimum` tidak bisa diturunkan di bawah nilai tertinggi yang sudah diinput untuk asesmen itu.
- Semester mata kuliah 1–8 dan jumlah SKS 1–24. `jenis_mata_kuliah`, `level_bloom_mk`, dan `kategori_cpl` berupa teks bebas. Form menampilkan saran: Wajib/Pilihan; C1–C6; Sikap/Pengetahuan/Keterampilan Umum/Keterampilan Khusus.
- Nilai ambang dari `.env` dianggap valid hanya jika berupa angka 0–100. Selain itu, perlakukan sebagai belum ditetapkan (lihat 5.3) dan catat peringatan di log.
- Saat menyimpan rencana asesmen, Σ `bobot_kontribusi` ke Sub-CPMK harus sama dengan `bobot_asesmen` (diblokir jika berbeda, karena keduanya diisi di form yang sama).
- Sub-CPMK yang bisa dipetakan ke asesmen hanya Sub-CPMK milik MK kelas tersebut (`sub_cpmk → pemetaan_cpmk_mk.mata_kuliah_id = kelas_kuliah.mata_kuliah_id`). Satu asesmen boleh dipetakan ke beberapa Sub-CPMK.
- Mahasiswa yang bisa dinilai hanya yang terdaftar di `peserta_kelas` kelas itu.
- Duplikasi pada kombinasi unik (pemetaan, peserta, dosen kelas, nilai) ditolak dengan pesan jelas.
- Kode PL, CPL, BK, MK, dan CPMK tidak boleh ganda dalam satu kurikulum, tetapi boleh sama di kurikulum lain. Kode Sub-CPMK tidak boleh ganda dalam satu pasangan CPMK–MK.
- Setiap pemetaan (CPL–PL, CPL–BK, BK–MK, CPMK–MK) dan relasi Profil Lulusan–Profesi hanya boleh menghubungkan data dari kurikulum yang sama.
- Periode akademik (tahun_akademik + semester_periode) dan kelas (MK + periode + nama_kelas) tidak boleh ganda. Ini divalidasi di aplikasi karena ERD tidak memberi UQ untuk keduanya.
- Seorang mahasiswa hanya boleh terdaftar di satu kelas untuk MK yang sama dalam satu periode. Dengan begitu aturan MK yang diulang (5.4) selalu punya satu kelas terbaru.
- Akun dan peran:
  - Peran DOSEN dan MAHASISWA hanya untuk akun yang punya data dosen/mahasiswa. Akun seperti itu dibuat lewat Data Master Dosen/Mahasiswa.
  - Setiap akun minimal punya satu peran.
  - Admin tidak bisa mencabut peran ADMIN miliknya sendiri atau menghapus akunnya sendiri.
  - Akun hanya bisa dihapus di menu Akun Pengguna jika tidak punya data dosen/mahasiswa.
- Peringatan (tidak memblokir), ditampilkan di halaman terkait:
  - Σ `bobot_asesmen` per kelas ≠ 100.
  - Σ `bobot_kontribusi` ke suatu Sub-CPMK dalam satu kelas ≠ `bobot_sub_cpmk`.
  - Σ `bobot_sub_cpmk` per `pemetaan_cpmk_mk` ≠ `bobot_cpmk`.
  - Σ `bobot_cpmk` per mata kuliah ≠ 100.
  - Metode asesmen tidak termasuk metode yang direncanakan untuk Sub-CPMK tersebut di `sub_cpmk_penilaian`.
- Menghapus data yang masih dipakai data lain → ditolak dengan pesan jelas.

## 7. Desain Antarmuka

Tata letak mengikuti mockup di proposal (Gambar 3.7–3.15). Mockup itu berupa wireframe abu-abu, jadi **warnanya mengikuti palet di 7.2**, bukan abu-abu mockup.

### 7.1 Tata letak umum

- **Top bar** (tinggi 56–64px, warna `primary-900`, teks putih): kiri tombol hamburger, nama aplikasi **SIMONEV-CPL** (tebal), dan di desktop diikuti judul lengkap "Sistem Monitoring & Evaluasi Capaian Pembelajaran"; kanan avatar bulat, nama peran aktif, dan chevron yang membuka menu akun (Profil, Ubah Password, Ganti Peran jika punya lebih dari satu peran, Keluar).
- **Sidebar** (lebar 256px, putih, garis kanan tipis): kotak logo Universitas Khairun di atas (file `public/images/logo-unkhair.png` saya sediakan; selama belum ada, tampilkan teks "UNKHAIR"), lalu menu ber-ikon. Menu aktif: latar `primary-50`, teks `primary-900`, dan garis kiri 3px warna `accent`. Grup menu (mis. Data Master) bisa dibuka-tutup dengan chevron; sub-menunya bertanda titik.
- **Area konten**: latar `bg`, judul halaman di kiri atas, isi di dalam kartu putih (sudut 12px, garis `border`, bayangan sangat tipis, padding 20–24px).
- Judul tab peramban berformat "<Nama halaman> · SIMONEV-CPL" (`APP_NAME=SIMONEV-CPL`).
- Pesan sukses atau gagal tampil sebagai toast. Hapus data memakai modal konfirmasi (bukan `confirm()` bawaan peramban) dengan tombol merah.

### 7.2 Warna (palet biru + kuning Unkhair)

Biru diambil dari gelombang laut pada lambang Unkhair, kuning dari bendera dan jas almamater. Definisikan semua warna sebagai token di satu tempat (blok `@theme` di `resources/css/app.css` untuk Tailwind v4, atau `theme.extend.colors` untuk Tailwind v3), lalu pakai lewat nama token, bukan hex langsung di view.

| Token | Hex | Dipakai untuk |
|---|---|---|
| `primary-900` | #1E3A8A | Top bar, teks menu aktif |
| `primary-800` | #1E40AF | Hover tombol utama |
| `primary-700` | #1D4ED8 | Tombol utama, tautan, tab aktif, halaman aktif di paginasi, ring fokus |
| `primary-100` | #DBEAFE | Lingkaran ikon pada kartu statistik |
| `primary-50` | #EFF6FF | Latar menu aktif dan baris terpilih |
| `accent` | #FACC15 | Garis penanda menu aktif dan aksen kecil. Jangan dipakai untuk teks di atas putih; teks di atas kuning memakai #422006 |
| `bg` | #F8FAFC | Latar area konten |
| `surface` | #FFFFFF | Kartu, sidebar, tabel, modal |
| `border` | #E2E8F0 | Garis kartu, tabel, dan input |
| `text` | #0F172A | Teks utama |
| `text-muted` | #475569 | Label, keterangan, header tabel |
| `success` | teks #166534, latar #DCFCE7, grafik #16A34A | Tercapai, Lulus, Tersimpan |
| `danger` | teks #991B1B, latar #FEE2E2, grafik #DC2626 | Tidak Tercapai, Belum Lulus, tombol hapus, pesan error |
| `warning` | teks #92400E, latar #FEF3C7 | Peringatan bobot tidak sesuai target |
| `neutral` | teks #475569, latar #F1F5F9 | Belum ada nilai, Ambang belum ditetapkan, status Belum di input nilai |

- Warna status sama di semua tempat (badge, donut, batang grafik): hijau = Tercapai/Lulus, merah = Tidak Tercapai/Belum Lulus, abu = belum bisa dinilai. Biru hanya untuk elemen interaktif dan identitas, bukan untuk status.
- Status tidak boleh hanya mengandalkan warna: badge selalu memuat teks dan ikon (centang/silang), dan batang grafik memuat label angka.
- Kontras teks minimal WCAG AA (4,5:1).

### 7.3 Tipografi, ikon, dan komponen

- Font **Plus Jakarta Sans** (400, 500, 600, 700), di-bundle lewat npm (`@fontsource/plus-jakarta-sans`) supaya tidak bergantung CDN; fallback `system-ui, sans-serif`.
- Ukuran: judul halaman 24px/600, judul kartu 18px/600, isi 14–16px, angka kartu statistik 24–28px/700.
- Ikon outline seperti di mockup (mis. Heroicons outline lewat `blade-ui-kit/blade-heroicons`), ukuran 20–24px.
- **Kartu statistik**: ikon dalam lingkaran `primary-100` (ikon `primary-700`), label kecil `text-muted`, angka besar.
- **Tombol**: satu tombol utama per halaman, biru terisi dengan teks putih (mis. Login, Tambah Data, Simpan Pemetaan, Simpan Nilai, Terapkan Filter). Tombol lain outline putih bergaris `border`. Tombol aksi di tabel berupa ikon kecil bergaris (ubah, hapus; hapus menjadi merah saat hover), masing-masing dengan `aria-label`.
- **Tabel**: header berlatar #F1F5F9 dengan teks `text-muted`, baris dipisah garis, hover #F8FAFC. Di bawah tabel: "Menampilkan 1–10 dari N data", tombol halaman (halaman aktif biru terisi), dan pilihan "10 / halaman".
- **Form**: label di atas input, tinggi input minimal 44px, sudut 8px, garis `border`, ring fokus `primary-700`, pesan error merah di bawah field. Select memakai ikon chevron.
- **Tab** (Laporan): gaya garis bawah; tab aktif bertulisan `primary-700` dengan garis bawah 2px.
- **Empty state**: satu kalimat dan tombol aksi, mis. "Belum ada data mahasiswa." dengan tombol Tambah Data.
- **Format angka dan tanggal**: angka memakai koma desimal dengan 2 angka di belakang koma, mis. "78,40" dan "78,40%" (seperti penulisan di buku APTIKOM). Tanggal seperti mockup, mis. "24 Okt 2026" (`translatedFormat('d M Y')`). Input nilai menerima koma maupun titik, lalu dinormalisasi ke titik sebelum validasi dan penyimpanan.

### 7.4 Responsif untuk pengguna mobile (KNF-04)

- Mobile-first dengan breakpoint Tailwind: < 768px (mobile), 768–1023px (tablet), ≥ 1024px (desktop). Semua halaman harus nyaman dipakai di lebar 360px tanpa geser horizontal di tingkat halaman.
- **Sidebar**: di ≥ 1024px selalu tampil, dan hamburger menyembunyikan/menampilkannya. Di < 1024px tersembunyi dan muncul sebagai drawer dari kiri dengan latar gelap transparan; tertutup saat latar disentuh, menu dipilih, atau tombol Esc ditekan.
- **Top bar di mobile**: hanya tampil nama SIMONEV-CPL (judul lengkap disembunyikan), dan nama peran disembunyikan (tinggal avatar).
- **Grid kartu**: kartu statistik 2 kolom di mobile; 3 kolom di desktop untuk Dashboard dan 4 kolom untuk Laporan. Kartu yang berdampingan di desktop (tabel + grafik, donut + tabel) ditumpuk di mobile.
- **Tabel daftar** (data master, kelas, rencana asesmen, hasil laporan): di < 768px setiap baris tampil sebagai kartu berisi pasangan label–nilai, dengan tombol aksi di bawah kartu.
- **Matriks** (pemetaan kurikulum, matriks bobot): tetap berbentuk tabel yang bisa digeser horizontal di dalam kartunya, dengan kolom pertama dan baris header menempel (sticky).
- **Form dan filter**: satu kolom di mobile. Panel filter di Laporan dan Input Nilai bisa dibuka-tutup lewat tombol "Filter".
- **Input nilai di mobile**: satu kartu per mahasiswa (NPM, nama, input nilai, status). Input memakai `inputmode="decimal"`. Tombol Simpan Nilai menempel di bawah layar, dan muncul peringatan jika pengguna meninggalkan halaman sebelum menyimpan.
- **Target sentuh** minimal 44×44px, termasuk lingkaran di matriks pemetaan. Ukuran font input minimal 16px di mobile agar Safari iOS tidak memperbesar halaman saat input disentuh.
- **Modal**: tampil sebagai bottom sheet di mobile dan dialog di tengah pada desktop. Paginasi di mobile cukup tombol Sebelumnya/Berikutnya dan teks "Hal 1 dari 5".
- **Grafik** Chart.js: `responsive: true`, `maintainAspectRatio: false`, dengan tinggi kontainer tetap (±240px di mobile, ±320px di desktop). Legenda donut pindah ke bawah grafik di mobile. Jika item banyak, grafik batang boleh menjadi horizontal di mobile.
- Setiap selesai membuat satu halaman, cek tampilannya di lebar 360px, 768px, dan 1280px.

### 7.5 Menu per peran

Menu mengikuti use case dan KF. Menu di mockup yang tidak ada di use case (Monitoring, Laporan, dan Pengaturan untuk Admin; Perencanaan dan Rekapitulasi untuk Koprodi; Evaluasi untuk Dosen) tidak dibuat.

- **Admin**: Beranda · Data Master ▸ (Program Studi, Kurikulum, Evaluasi Kurikulum, Profil Lulusan, Profesi, CPL, Bahan Kajian, Mata Kuliah, CPMK, Sub-CPMK, Metode Penilaian, Dosen, Mahasiswa) · Pemetaan Kurikulum · Perkuliahan ▸ (Periode Akademik, Kelas Kuliah) · Akun Pengguna.
- **Koprodi**: Dashboard · Data Kurikulum (read-only, termasuk evaluasi kurikulum dan pemetaan) · Laporan Capaian.
- **Dosen**: Dashboard · Mata Kuliah · Asesmen ▸ (Rencana Asesmen, Input Nilai) · Laporan Capaian.
- **Mahasiswa**: Capaian Pembelajaran.
- Profil dan Ubah Password ada di menu akun (top bar) untuk semua peran.

### 7.6 Halaman

1. **Login** (semua, Gambar 3.7): top bar biru dengan judul aplikasi; latar `bg` dengan ornamen lingkaran tipis berwarna `primary-50` (opsional); kartu putih di tengah (lebar maks. 400px) berisi logo, nama SIMONEV-CPL, judul "Masuk ke Sistem", input "Email / NPM / NIDN" dengan ikon pengguna, input password dengan ikon gembok dan tombol tampil/sembunyikan, serta tombol utama "Login" selebar kartu. NPM dicocokkan ke `mahasiswa.nim` dan NIDN ke `dosen.nidn`, lalu ke akun `users` miliknya. Setelah berhasil, pengguna diarahkan sesuai peran.
2. **Dashboard Monitoring** (Koprodi, Gambar 3.8): dropdown Kurikulum di kanan atas (default kurikulum terbaru). Enam kartu statistik: jumlah mahasiswa, jumlah mata kuliah, jumlah kelas aktif, jumlah CPMK, jumlah CPL, dan periode akademik aktif; jumlah MK, CPMK, CPL, dan kelas aktif mengikuti kurikulum terpilih. Di bawahnya kartu "Ringkasan Capaian CPL": donut Tercapai (hijau) vs Tidak Tercapai (merah) berdasarkan aturan 5.3 dengan filter angkatan (default semua angkatan), dan di sebelahnya tabel Kategori, Jumlah CPL, Persentase, dan Total. Label kategori menyebut ambang yang berlaku, mis. "Tercapai (≥ 85% mahasiswa lulus)". CPL yang belum punya nilai sama sekali masuk kategori ketiga "Belum ada nilai" berwarna abu (`neutral`). Jika ambang di `.env` belum diisi, ganti donut dengan pemberitahuan "Ambang ketercapaian belum ditetapkan program studi". Ringkasan capaian CPL di dashboard selalu kumulatif dari semua periode, mengikuti filter kurikulum dan angkatan (lihat 5.3).
3. **Beranda Admin**: kartu jumlah mahasiswa, dosen, mata kuliah (kurikulum terbaru), dan kelas (periode terbaru), ditambah pintasan ke menu yang sering dipakai. Tidak menampilkan data capaian.
4. **Kelola Data Master** (Admin, Gambar 3.9): judul "Kelola Data Master <Entitas>"; di kanan atas kotak pencarian "Cari data..." dan tombol "Tambah Data"; tabel dengan kolom Aksi (ubah, hapus) dan paginasi. Entitas: Program Studi, Kurikulum, Evaluasi Kurikulum, Profil Lulusan, Profesi, CPL, Bahan Kajian, Mata Kuliah, CPMK, Sub-CPMK, Metode Penilaian, Dosen, Mahasiswa.
   - Tabel Mahasiswa berkolom NPM, Nama Mahasiswa, Angkatan, Aksi. Kolom Status di mockup tidak dibuat karena tidak ada di ERD.
   - Halaman data yang bergantung pada kurikulum (Evaluasi Kurikulum, Profil Lulusan, Profesi, CPL, Bahan Kajian, Mata Kuliah, CPMK, Sub-CPMK) punya dropdown Kurikulum di atas tabel; data baru otomatis masuk ke kurikulum terpilih.
   - Evaluasi Kurikulum: baris-baris hasil evaluasi per kurikulum (tahap_evaluasi, kinerja_mutu, standar_kinerja_mutu, kesenjangan, tindak_lanjut), diisi dari dokumen evaluasi kurikulum prodi sesuai format Tabel B APTIKOM.
   - Form Dosen/Mahasiswa sekaligus membuat akun `users` dan memberi peran. Nama disimpan sama di `users.nama` dan `dosen.nama`/`mahasiswa.nama`. Menghapus data Dosen/Mahasiswa ikut menghapus akunnya. Jika akun itu masih punya peran lain (mis. KOPRODI), akun tetap ada dan hanya peran DOSEN/MAHASISWA yang dicabut.
   - Relasi Profil Lulusan–Profesi diisi lewat multi-select di form Profil Lulusan.
   - Form Sub-CPMK memilih pasangan CPMK–MK; rincian penilaian Sub-CPMK (`sub_cpmk_penilaian`: metode, teknik, instrumen, kriteria) dikelola di halaman detail Sub-CPMK.
   - Halaman detail Mata Kuliah menampilkan ringkasan bobot CPMK dan Sub-CPMK beserta totalnya (harus 100), mirip Tabel 19 APTIKOM.
   - Akun Pengguna (menu tersendiri): daftar user, tambah/ubah, atur peran, reset password. Tambah akun di menu ini untuk akun Admin/Koprodi (email wajib). Peran DOSEN dan MAHASISWA hanya bisa dicentang untuk akun yang punya data dosen/mahasiswa (lihat bagian 6).
5. **Pemetaan Kurikulum** (Admin, Gambar 3.10): kartu filter berisi dropdown Jenis Pemetaan (CPL–Profil Lulusan, CPL–Bahan Kajian, Bahan Kajian–Mata Kuliah, CPMK–Mata Kuliah) dan dropdown Kurikulum. Kartu matriks berisi baris × kolom dengan lingkaran yang bisa diklik (terisi biru = terhubung, kosong = tidak). Untuk BK–MK dan CPMK–MK, sel yang terhubung menampilkan input bobot. Tombol utama "Simpan Pemetaan" di kanan bawah (menempel di bawah layar pada mobile).
6. **Periode Akademik & Kelas Kuliah** (Admin, Gambar 3.11): CRUD periode akademik. Halaman kelas: dropdown Periode Akademik dan tombol "Tambah Kelas"; tabel Mata Kuliah, Kelas, Dosen, Peserta (jumlah), Aksi (lihat detail, ubah). Di detail kelas, Admin mengatur dosen pengampu (boleh lebih dari satu) dan peserta (cari mahasiswa, atau tambah sekaligus per angkatan).
7. **Kelola Rencana Asesmen** (Dosen, Gambar 3.12): tiga kartu info ber-ikon (Mata Kuliah, Kelas, Periode) dan tombol "Tambah Asesmen". Tabel Asesmen, Metode, Bobot (%), Sub-CPMK (kode), Nilai Maks, Tanggal, Aksi. Form: metode penilaian, nama asesmen, bobot asesmen, nilai maksimum, tanggal, lalu pilih satu atau lebih Sub-CPMK beserta bobot kontribusinya (dalam % nilai akhir MK). Di bawah tabel tampilkan **matriks bobot** seperti bagian Penilaian RPS APTIKOM: baris = Sub-CPMK (dikelompokkan per CPMK), kolom = asesmen, sel = `bobot_kontribusi`, lengkap dengan total per baris/kolom, target (`bobot_sub_cpmk`, 100), dan tanda kuning (`warning`) jika tidak cocok.
8. **Input Nilai Asesmen** (Dosen, Gambar 3.13): kartu filter berisi dropdown bertingkat Mata Kuliah → Periode → Kelas → Rencana Asesmen (hanya kelas yang diampu) dan tombol utama "Simpan Nilai". Tabel NPM, Nama Mahasiswa, Nilai (input angka), Status (badge Tersimpan hijau / Belum abu). Simpan Nilai menyimpan seluruh baris sekaligus; jika ada nilai yang tidak valid, tidak ada yang disimpan dan baris yang salah ditandai merah.
9. **Laporan Capaian Pembelajaran** (Koprodi: seluruh prodi; Dosen: hanya kelasnya; Gambar 3.14): kartu filter (kurikulum [default terbaru], periode, angkatan, mata kuliah, kelas, mahasiswa, CPL, CPMK, Sub-CPMK) dan tombol utama "Terapkan Filter". Di bawahnya, tampilkan ambang yang sedang berlaku (AM dan AK), atau "Belum ditetapkan". Tab CPL | CPMK | Sub-CPMK. Empat kartu ringkasan: rata-rata capaian, capaian tertinggi (dengan kode), capaian terendah (dengan kode), jumlah item. Di kiri, tabel hasil berkolom kode, rata-rata capaian (%), % mahasiswa Lulus (≥ AM), dan status (Tercapai/Tidak Tercapai berdasarkan AK). Di kanan, grafik batang % mahasiswa Lulus per item dengan label angka di atas batang dan garis putus-putus AK; batang hijau jika Tercapai dan merah jika tidak (biru jika ambang belum ditetapkan). Di atas hasil, keterangan kecil menyebut cakupan periode: "CPL dihitung dari kelas pada periode ini saja" atau "CPL kumulatif semua periode" (lihat 5.3). Pada tab CPMK dan Sub-CPMK tanpa filter MK, setiap baris adalah satu pasangan dengan MK-nya, berlabel "CPMK063 · MK04" atau "Sub-CPMK0811 · MK04" (memakai kode MK supaya label grafik tetap pendek), diurutkan berdasarkan kode CPMK (atau kode Sub-CPMK) lalu kode MK supaya kode yang sama letaknya berdekatan. Jika filter satu MK dipilih, label cukup kode CPMK atau Sub-CPMK.
10. **Capaian Pembelajaran Mahasiswa** (Mahasiswa, Gambar 3.15): kartu identitas (avatar, Nama, NPM); dropdown Periode dan Mata Kuliah; bagian "Ringkasan" berupa 3 kartu angka besar (rata-rata capaian Sub-CPMK dan CPMK pada mata kuliah terpilih, serta rata-rata capaian CPL kumulatif); tabel "Detail Nilai Asesmen" (No., Asesmen, Nilai); rincian capaian per Sub-CPMK, CPMK, dan CPL dengan badge Lulus/Belum Lulus (≥ AM); serta ringkasan CPL kumulatif dari semua MK yang telah diikuti (dipisah per kurikulum jika mahasiswa punya data di dua kurikulum). Tidak ada aksi ubah.
11. **Halaman Dosen lainnya**: Dashboard berisi kartu kelas yang diampu pada periode terbaru (mata kuliah, kelas, periode, jumlah peserta); halaman Mata Kuliah menampilkan CPL, CPMK, dan Sub-CPMK terkait (KF-07).
12. **Data Kurikulum** (Koprodi): menampilkan data kurikulum, evaluasi kurikulum, dan matriks pemetaan secara read-only, dengan pemilih kurikulum.
13. **Profil dan Ubah Password** (semua peran, dari menu akun): menampilkan nama, email, NPM/NIDN, dan peran; ubah password dengan memasukkan password lama, password baru (minimal 8 karakter), dan konfirmasinya.
14. **Halaman error** 403, 404, 419 (sesi habis), dan 500 dalam bahasa Indonesia dengan tata letak yang sama, mis. 403: "Anda tidak memiliki akses ke halaman ini." dengan tombol kembali ke beranda.

## 8. Kebutuhan Nonfungsional

- KNF-01: berbasis web, diakses melalui peramban.
- KNF-02: antarmuka sederhana dan mudah dipahami.
- KNF-03: autentikasi dan pembatasan akses berdasarkan peran; password di-hash, proteksi CSRF aktif (bawaan Laravel).
- KNF-04: tampilan responsif dan nyaman dipakai di perangkat mobile, mulai dari lebar layar 360px (lihat 7.4).

## 9. Di Luar Cakupan (jangan dibuat)

KRS, KHS, jadwal kuliah, keuangan, dan administrasi akademik lain; evaluasi dosen oleh mahasiswa; fitur keamanan di luar autentikasi dan pembatasan hak akses; aplikasi mobile native (Android/iOS); impor data dari Excel/CSV; ekspor laporan ke PDF/Excel; riwayat (audit log) perubahan nilai; panduan deploy ke server. Versi web tetap harus responsif (KNF-04).

## 10. Data Awal (Seeder)

- Roles: ADMIN, KOPRODI, DOSEN, MAHASISWA.
- Akun demo untuk tiap peran, plus satu akun yang memiliki peran KOPRODI dan DOSEN sekaligus. Password sederhana untuk pengembangan lokal saja, dengan pola bagian email sebelum `@` ditambah `123` (mis. `admin123`), supaya mudah diketik saat mencoba aplikasi.
- Data contoh secukupnya agar dashboard dan laporan terisi: 1 program studi, 2 kurikulum (2021 sebagai kurikulum lama dan 2026 sebagai kurikulum baru, dengan beberapa kode yang sama di keduanya, mis. CPL01 dan satu kode MK), beberapa baris evaluasi kurikulum, beberapa Profil Lulusan, CPL, Bahan Kajian, mata kuliah (mis. Matematika Diskrit, Algoritma, Basis Data, Struktur Data, Pemrograman Web), CPMK, Sub-CPMK, metode penilaian (Kuis, Tugas, UTS, UAS, Proyek, Praktikum), periode 2026/2027 Ganjil, kelas dari kedua kurikulum, peserta dari minimal dua angkatan, rencana asesmen, dan nilai. **Semua bobot di seeder harus memenuhi aturan 5.1.** Data contoh diisi bertahap mengikuti iterasi yang membutuhkannya. Dosen dan mahasiswa contoh selain akun demo utama tidak punya email; mereka login dengan NIDN/NPM dan password `dosen123` atau `mahasiswa123`.
- Data kurikulum asli prodi akan saya masukkan sendiri nanti.
- `.env.example` memuat `AMBANG_KELULUSAN_MAHASISWA=50.01` dan `AMBANG_KETERCAPAIAN=85` (contoh APTIKOM, hanya untuk pengembangan), dengan komentar bahwa nilainya harus diganti sesuai ketetapan prodi.

## 11. Urutan Pengerjaan (Personal Extreme Programming, iteratif)

Kerjakan per iterasi. Di setiap iterasi: tulis test → tulis kode → refactor → jalankan test. Sebelum berhenti, perbarui `CHANGELOG.md`, tulis catatan partisi di `docs/catatan-kode/`, catat keputusan baru di `docs/keputusan-desain.md`, lalu commit, push, dan buat Pull Request (bagian 15 dan 16). Setelah itu berhenti dan beri ringkasan: apa yang selesai, cara mencobanya, dan apa yang perlu saya putuskan sebelum lanjut.

1. Inisialisasi repositori (README.md, CHANGELOG.md, folder `docs/`), setup proyek, migrasi semua tabel dengan PK UUID, model + relasi, seeder peran & akun demo, login, middleware peran, pilih/ganti peran aktif. Termasuk Profil & Ubah Password (7.6.13), halaman error 403/404/419/500 (7.6.14), dan halaman sambutan sementara sebagai halaman utama tiap peran.
2. Kelola akun pengguna, Dosen, Mahasiswa, dan seluruh data master kurikulum kecuali Sub-CPMK, termasuk evaluasi kurikulum dan validasi kode unik per kurikulum. Termasuk Beranda Admin (7.6.3).
3. Pemetaan kurikulum (matriks), lalu data master Sub-CPMK beserta rincian penilaiannya dan ringkasan bobot di detail Mata Kuliah (Sub-CPMK menempel ke pasangan CPMK–MK hasil pemetaan). Termasuk halaman Data Kurikulum Koprodi yang read-only (7.6.12) dengan memakai ulang tampilan matriks.
4. Periode akademik, kelas kuliah, dosen pengampu, peserta kelas.
5. Rencana asesmen + pemetaan asesmen ke Sub-CPMK + matriks bobot (Dosen). Termasuk Dashboard Dosen dan halaman Mata Kuliah Dosen (7.6.11, KF-07).
6. Input nilai asesmen.
7. `CapaianService` + unit test (Uji 1–6 di bagian 5.5).
8. Dashboard Koprodi, laporan capaian + filter (Koprodi & Dosen).
9. Halaman capaian mahasiswa.
10. Feature test untuk seluruh skenario di bagian 12, pengecekan tampilan mobile (7.4), dokumen hasil pengujian (bagian 12), lalu perbaikan.

## 12. Skenario Uji Black Box (kriteria penerimaan)

| No | Fitur | Skenario | Hasil yang diharapkan |
|---|---|---|---|
| 1 | Login | Pengguna memasukkan data login yang valid | Diarahkan ke halaman sesuai hak akses |
| 2 | Login | Pengguna memasukkan data login yang salah | Login ditolak dan muncul pesan kesalahan |
| 3 | Hak akses | Pengguna membuka fitur di luar kewenangannya | Akses ditolak |
| 4 | Data master kurikulum | Admin menambahkan data master | Data tersimpan dan tampil |
| 5 | Pemetaan kurikulum | Admin menyimpan hubungan antarkomponen kurikulum | Pemetaan tersimpan |
| 6 | Kelas kuliah | Admin membuat kelas dan menentukan dosen/peserta | Data kelas, dosen, dan peserta tersimpan |
| 7 | Rencana asesmen | Dosen menambahkan asesmen pada kelas yang diampu | Rencana asesmen tersimpan |
| 8 | Pemetaan asesmen–Sub-CPMK | Dosen menghubungkan asesmen dengan Sub-CPMK | Hubungan tersimpan |
| 9 | Input nilai | Dosen memasukkan nilai peserta kelas | Nilai tersimpan |
| 10 | Input nilai | Dosen memasukkan nilai di atas nilai maksimum | Ditolak / muncul pesan validasi |
| 11 | Perhitungan capaian | Nilai asesmen tersedia lengkap | Capaian Sub-CPMK, CPMK, dan CPL terhitung |
| 12 | Dashboard | Koprodi membuka dashboard | Ringkasan capaian tampil |
| 13 | Filter laporan | Koprodi/Dosen memilih kriteria laporan | Data tampil sesuai filter |
| 14 | Laporan dosen | Dosen membuka laporan kelas yang diampu | Capaian kelas tersebut tampil |
| 15 | Capaian mahasiswa | Mahasiswa membuka halaman capaian pribadi | Hanya nilai dan capaian mahasiswa itu yang tampil |
| 16 | Tampilan mobile | Pengguna membuka setiap halaman di layar selebar 360px | Halaman tampil utuh dan bisa dipakai tanpa geser horizontal (kecuali isi matriks) |

Setelah iterasi 10, buat `docs/pengujian/black-box.md` untuk Bab 4 skripsi: tabel No, Fitur, Skenario, Langkah, Data uji, Hasil yang diharapkan, Hasil pengujian, dan Status (Berhasil/Gagal) untuk semua skenario di atas, diisi dari hasil feature test. Sertakan juga daftar tangkapan layar yang perlu saya ambil untuk setiap skenario dan setiap halaman (desktop dan mobile).

## 13. Keputusan dan Asumsi (konfirmasi ke saya jika ragu)

Daftar ini juga menjadi isi awal `docs/keputusan-desain.md` (bagian 15).

- Stack tampilan: Blade + Tailwind + Alpine.js + Chart.js.
- Metode perhitungan dan arti bobot mengikuti contoh APTIKOM (bagian 5): semua bobot dalam % nilai akhir MK, CPL = Σ skor / Σ skor maks, SKS tidak dipakai sebagai bobot.
- Status memakai dua ambang (AM dan AK) yang disimpan di `.env` dan berlaku global, sehingga ERD tidak berubah. Nilainya ditetapkan prodi dan diisi pengelola server; `.env.example` memakai contoh APTIKOM (50.01 dan 85).
- Nilai kosong diabaikan (bobot dinormalisasi ulang), bukan dihitung 0.
- Mahasiswa yang mengulang MK: dipakai kelas terbaru yang sudah punya minimal satu nilai asesmen (lihat 5.4), untuk CPL maupun tab CPMK dan Sub-CPMK laporan.
- "Periode akademik aktif" = periode terbaru yang sudah memiliki kelas (ERD tidak punya kolom status aktif). Periode yang dibuat lebih awal tetapi belum berisi kelas tidak mengubah periode aktif.
- Sistem menangani lebih dari satu kurikulum (sekarang dan yang akan datang). Kurikulum mahasiswa tidak disimpan; CPL mahasiswa mengikuti kurikulum dari MK yang ia ikuti.
- Perhitungan memakai bobot dari rencana asesmen tiap kelas. Bobot Sub-CPMK dan CPMK di kurikulum hanya target untuk validasi, sehingga revisi bobot tidak mengubah laporan lama.
- Evaluasi kurikulum dikelola Admin di menu Data Master Kurikulum (bagian dari KF-03); Koprodi hanya melihat.
- Login memakai email, NPM, atau NIDN. Tampilan memakai label "NPM" seperti mockup, sedangkan kolom database tetap `nim` sesuai ERD.
- Menu tiap peran mengikuti use case dan KF (lihat 7.5), bukan menu di mockup.
- Tata letak mengikuti mockup; warna memakai palet biru + kuning Unkhair (7.2); font Plus Jakarta Sans.
- Angka ditampilkan dengan koma desimal ("78,40") seperti buku APTIKOM, berbeda dari mockup yang memakai titik.
- Akun hanya dibuat Admin; tidak ada registrasi publik maupun reset password lewat email.
- Sistem dijalankan di laptop (localhost) untuk pengujian dan demo. Tidak ada fitur di luar KF (impor, ekspor, audit log) dan tidak ada panduan deploy ke server.
- Kolom Status pada mockup Data Mahasiswa tidak dibuat karena tidak ada di ERD.

## 14. Gaya Kode: Selayaknya Mahasiswa, Tetap Clean Code

Kode harus wajar ditulis oleh mahasiswa S1 tingkat akhir dan mudah saya jelaskan saat sidang, tetapi tetap rapi.

- Ikuti struktur standar Laravel: `routes/web.php` → Controller → Form Request (validasi) → Model (Eloquent dan relasinya) → View (Blade). Controller dibuat tipis. Logika perhitungan capaian hanya ada di `app/Services/CapaianService.php`.
- Hindari pola yang berlebihan untuk proyek sebesar ini: repository pattern, interface untuk setiap class, DDD, CQRS, event sourcing, macro, atau trik meta-programming. Tambahkan paket pihak ketiga hanya jika jelas menghemat pekerjaan, dan tulis alasannya di catatan.
- Penamaan: istilah domain mengikuti ERD dalam bahasa Indonesia (model `MataKuliah`, `RencanaAsesmen`, method `hitungCapaianCpl()`), sedangkan istilah teknis Laravel tetap bahasa Inggris (`index`, `store`, `update`, `destroy`). Nama variabel jelas dan lengkap, mis. `$nilaiNormalisasi`, bukan `$nn`.
- Satu fungsi satu tugas, dan fungsinya pendek. Jangan menduplikasi kode: kartu, tabel, badge status, tombol, dan modal dibuat sebagai Blade component.
- Komentar dalam bahasa Indonesia untuk menjelaskan *mengapa*, terutama di bagian perhitungan (sebutkan nomor rumus di proposal atau tabel APTIKOM yang diikuti). Jangan mengomentari hal yang sudah jelas dari kodenya.
- Beri PHPDoc singkat pada method publik di service.
- Rapikan format dengan Laravel Pint (preset laravel) sebelum commit.
- Nama test menjelaskan perilaku dalam bahasa Indonesia, mis. `dosen_tidak_bisa_membuka_kelas_yang_tidak_diampu`.
- Teks antarmuka dan pesan error dalam bahasa Indonesia.

## 15. Repositori GitHub dan Pencatatan Perubahan

- Repositori: `https://github.com/<username>/simonev-cpl` (private). Kerjakan di salinan lokal repo ini.
- Branch `main` harus selalu bisa dijalankan dengan semua test lulus. Setiap iterasi di bagian 11 dikerjakan di branch sendiri, mis. `iterasi-01-setup`, `iterasi-02-data-master`, lalu digabung ke `main` lewat Pull Request setelah test lulus. Judul PR = nama iterasi; isinya ringkasan perubahan, cara menguji, dan hasil test.
- Commit kecil dan sering, satu perubahan logis per commit. Format pesan mengikuti Conventional Commits dengan deskripsi bahasa Indonesia, mis.:
  - `feat(asesmen): tambah form rencana asesmen`
  - `fix(nilai): tolak nilai di atas nilai maksimum`
  - `test(capaian): tambah uji data Tabel K APTIKOM`
  - `docs(catatan): jelaskan alur input nilai`
- Jangan commit `.env`, `vendor/`, `node_modules/`, atau data mahasiswa asli. Yang di-commit hanya `.env.example`.
- **Catat setiap perubahan** di tiga tempat:
  - `CHANGELOG.md` (format Keep a Changelog, bahasa Indonesia). Setiap perubahan yang mengubah perilaku atau tampilan dicatat di bagian `[Belum dirilis]` dengan kategori Ditambahkan, Diubah, Diperbaiki, atau Dihapus. Saat iterasi selesai, bagian itu dipindah ke judul iterasi beserta tanggalnya.
  - `docs/keputusan-desain.md`. Setiap keputusan desain dicatat dengan tanggal, keputusan, alasan, dan dampaknya. Isi awalnya adalah daftar di bagian 13.
  - Pesan commit dan deskripsi Pull Request seperti aturan di atas.
- `README.md` berisi deskripsi singkat sistem, cara instalasi dan menjalankan di laptop (`composer install`, `npm install`, salin `.env.example`, `php artisan key:generate`, `php artisan migrate --seed`, `npm run dev`, `php artisan serve`), cara mengembalikan data demo (`php artisan migrate:fresh --seed`), daftar akun demo, dan cara menjalankan test.
- Sistem hanya dijalankan di laptop (localhost) untuk pengujian dan demo sidang. Tulis juga di README cara membukanya dari HP di Wi-Fi yang sama untuk menunjukkan tampilan mobile: `npm run build`, lalu `php artisan serve --host=0.0.0.0 --port=8000`, kemudian buka `http://<IP-laptop>:8000` di HP (izinkan port 8000 di firewall jika perlu).

## 16. Catatan Penjelasan Kode (untuk Saya Pelajari)

Buat catatan di folder `docs/catatan-kode/` supaya saya bisa membaca dan memahami setiap bagian kode.

- Mulai dengan `00-gambaran-umum.md`: arsitektur aplikasi (alur request Laravel: route → middleware → controller → form request → model → view), struktur folder yang dipakai, daftar tabel dan modelnya, serta cara menjalankan proyek dan test.
- Buat satu file untuk setiap partisi atau iterasi, mis. `01-setup-autentikasi-peran.md`, `02-data-master.md`, `07-perhitungan-capaian.md`. Tulis setelah partisi itu selesai, dan perbarui jika kodenya berubah.
- Tulis dalam bahasa Indonesia yang sederhana, untuk mahasiswa yang baru belajar Laravel. Isi setiap catatan:
  1. Tujuan partisi dan KF/KNF yang dipenuhi.
  2. Daftar file yang dibuat atau diubah, beserta peran masing-masing.
  3. Alur kerja dari aksi pengguna sampai data tersimpan atau tampil (boleh dengan diagram Mermaid).
  4. Penjelasan potongan kode penting: kutip potongan pendek, lalu jelaskan per blok.
  5. Hubungan dengan proposal: tabel ERD, nomor rumus, atau aturan APTIKOM yang dipakai.
  6. Konsep Laravel yang muncul (mis. relasi Eloquent, Form Request, Policy, middleware, Blade component, UUID) dengan penjelasan singkat.
  7. Cara mencoba secara manual dan cara menjalankan test-nya.
  8. Ringkasan iterasi: tanggal, hasil test, kendala, dan hal yang perlu saya putuskan.
  9. Beberapa pertanyaan yang mungkin ditanyakan penguji tentang bagian ini, beserta jawaban singkatnya.
- Catatan tidak menggantikan komentar di kode; keduanya saling melengkapi.
