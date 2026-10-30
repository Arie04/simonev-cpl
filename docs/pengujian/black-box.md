# Hasil Pengujian Black Box SIMONEV-CPL

Dokumen ini adalah bahan Bab 4 skripsi. Isinya hasil pengujian black box untuk 16 skenario di
bagian 12 spesifikasi, ditambah daftar tangkapan layar yang perlu diambil.

## 1. Lingkungan dan cara pengujian

| Butir | Keterangan |
|---|---|
| Tanggal pengujian | 30 Oktober 2026 |
| Perangkat lunak | Laravel 13, PHP 8.3.6, MySQL 8.0.46, Google Chrome 156 (mode headless) |
| Data uji | Data demo dari seeder (`php artisan migrate:fresh --seed`). Semua data fiktif, tidak ada data mahasiswa asli. |
| Skenario 1–15 | Feature test `tests/Feature/SkenarioBlackBoxTest.php`, satu test per skenario. Langkah dan datanya sama dengan tabel di bagian 3. |
| Skenario 16 | Skrip peramban `scripts/cek-tampilan.mjs` (`npm run cek:tampilan`), ditambah test sisi server `test_skenario_16_...`. |

### Mengulang pengujian

```bash
# Skenario 1–16 (sisi server); setiap test mengisi ulang data demo di database test.
php artisan test --filter=SkenarioBlackBoxTest

# Skenario 16 di peramban: butuh data demo, aset ter-build, dan aplikasi berjalan.
php artisan migrate:fresh --seed
npm run build
php artisan serve        # biarkan berjalan, lalu di terminal lain:
npm run cek:tampilan
```

### Catatan untuk pengujian manual

- **Urutan.** Jalankan skenario secara berurutan dari data demo yang baru diisi ulang. Skenario 7
  dan 8 memakai kelas INF102 B yang dibuat di skenario 6.
- **Isi ulang data sebelum skenario 11.** Skenario 4–9 mengubah data. Jalankan
  `php artisan migrate:fresh --seed` sebelum skenario 11 supaya angka capaian sama dengan tabel.
  Skenario 11–16 hanya membaca data.
- **Ambang.** Ambang yang dipakai sama dengan `.env.example`: AM = 50,01 dan AK = 85%.

## 2. Ringkasan hasil

| Butir | Hasil |
|---|---|
| Skenario berhasil | 16 dari 16 |
| Test skenario (`SkenarioBlackBoxTest`) | 16 test, 216 assertion, semua lulus (±25 detik) |
| Seluruh test proyek (`php artisan test`) | 289 test, 1.968 assertion, semua lulus |
| Pengecekan tampilan (`npm run cek:tampilan`) | 51 halaman × 3 lebar layar = 153 tangkapan layar, tanpa masalah |

Keluaran test (`vendor/bin/phpunit --filter SkenarioBlackBoxTest --testdox`):

```text
Skenario Black Box (Tests\Feature\SkenarioBlackBox)
 ✔ Skenario 01 login dengan data valid diarahkan ke halaman sesuai hak akses
 ✔ Skenario 02 login dengan data salah ditolak dengan pesan
 ✔ Skenario 03 membuka fitur di luar kewenangan ditolak
 ✔ Skenario 04 admin menambahkan data master kurikulum
 ✔ Skenario 05 admin menyimpan pemetaan kurikulum
 ✔ Skenario 06 admin membuat kelas dan menentukan dosen serta peserta
 ✔ Skenario 07 dosen menambahkan rencana asesmen pada kelas yang diampu
 ✔ Skenario 08 dosen menghubungkan asesmen dengan sub cpmk
 ✔ Skenario 09 dosen memasukkan nilai peserta kelas
 ✔ Skenario 10 nilai di atas nilai maksimum ditolak
 ✔ Skenario 11 capaian terhitung jika nilai asesmen lengkap
 ✔ Skenario 12 koprodi membuka dashboard dan ringkasan capaian tampil
 ✔ Skenario 13 laporan tampil sesuai filter
 ✔ Skenario 14 dosen membuka laporan kelas yang diampu
 ✔ Skenario 15 mahasiswa hanya melihat nilai dan capaian miliknya
 ✔ Skenario 16 semua halaman bisa dibuka dan siap untuk layar mobile

OK (16 tests, 216 assertions)
```

Keluaran pengecekan tampilan (`npm run cek:tampilan`):

```text
153 tangkapan layar disimpan di storage/app/tangkapan-layar/.
Semua halaman lolos: tidak bergeser horizontal, status benar, tanpa error JavaScript,
font input di mobile minimal 16px, dan target sentuh minimal 44×44 px.
```

## 3. Tabel pengujian

| No | Fitur | Skenario | Langkah | Data uji | Hasil yang diharapkan | Hasil pengujian | Status |
|---|---|---|---|---|---|---|---|
| 1 | Login | Pengguna memasukkan data login yang valid | 1. Buka halaman login.<br>2. Isi Email/NPM/NIDN dan password.<br>3. Klik **Login**.<br>4. Untuk akun dua peran, pilih peran Dosen. | Admin: `admin@simonev.test` / `admin123`<br>Koprodi: `koprodi@simonev.test` / `koprodi123`<br>Dosen: NIDN `9990000001` / `dosen123`<br>Mahasiswa: NPM `9992300001` / `mahasiswa123`<br>Dua peran: `koprodi.dosen@simonev.test` / `koprodi.dosen123` | Diarahkan ke halaman sesuai hak akses | Admin diarahkan ke Beranda Admin, Koprodi ke Dashboard Koprodi, Dosen ke Dashboard Dosen, dan Mahasiswa ke Capaian Pembelajaran. Akun dua peran masuk ke halaman Pilih Peran (Dosen dan Koordinator Program Studi), lalu ke Dashboard Dosen setelah memilih Dosen. | Berhasil |
| 2 | Login | Pengguna memasukkan data login yang salah | 1. Buka halaman login.<br>2. Isi email yang terdaftar dengan password yang salah.<br>3. Klik **Login**. | `admin@simonev.test` / `salah123` | Login ditolak dan muncul pesan kesalahan | Pengguna kembali ke halaman login dan belum masuk. Di bawah field muncul pesan "Email/NPM/NIDN atau password salah." | Berhasil |
| 3 | Hak akses | Pengguna membuka fitur di luar kewenangannya | 1. Tanpa login, buka `/admin`.<br>2. Login sebagai Dosen, buka `/admin` dan `/koprodi/laporan`.<br>3. Masih sebagai Dosen, buka Rencana Asesmen kelas INF101 A lewat alamat `/dosen/kelas/{id}/rencana-asesmen`. Id kelas disalin dari alamat detail kelas di akun Admin.<br>4. Login sebagai Mahasiswa, buka `/dosen/nilai` dan `/koprodi`. | Dosen `9990000001` (tidak mengampu INF101 A), Mahasiswa `9992300001` | Akses ditolak | Tamu diarahkan ke halaman login. Semua alamat lain menampilkan halaman 403 "Akses Ditolak". | Berhasil |
| 4 | Data master kurikulum | Admin menambahkan data master | 1. Login sebagai Admin.<br>2. Buka **Data Master → CPL** dan pilih Kurikulum OBE 2026.<br>3. Klik **Tambah**, isi form, lalu **Simpan**. | Kode: `CPL06`<br>Kategori: Keterampilan Khusus<br>Deskripsi: "Mampu menerapkan prinsip keamanan informasi dalam pengembangan perangkat lunak." | Data tersimpan dan tampil | Muncul pesan "CPL berhasil ditambahkan." CPL06 tersimpan di Kurikulum OBE 2026 dan tampil di daftar CPL. | Berhasil |
| 5 | Pemetaan kurikulum | Admin menyimpan hubungan antarkomponen kurikulum | 1. Login sebagai Admin.<br>2. Buka **Pemetaan Kurikulum**, tab CPL – Profil Lulusan, Kurikulum OBE 2026.<br>3. Centang lingkaran CPL04 – PL02.<br>4. Klik **Simpan Pemetaan**. | CPL04 – PL02 (sebelumnya CPL04 hanya terhubung ke PL01) | Pemetaan tersimpan | Muncul pesan "Pemetaan CPL – Profil Lulusan berhasil disimpan." CPL04 kini terhubung ke PL01 dan PL02, dan hubungan lain tidak berubah (mis. CPL01 tetap ke PL01, PL02, PL03). | Berhasil |
| 6 | Kelas kuliah | Admin membuat kelas dan menentukan dosen/peserta | 1. Login sebagai Admin.<br>2. Buka **Perkuliahan → Kelas Kuliah**, klik **Tambah**, isi form, lalu **Simpan**.<br>3. Di detail kelas, tambahkan dosen pengampu.<br>4. Tambahkan satu peserta, lalu peserta satu angkatan. | Kelas `B`, MK INF102 Algoritma dan Pemrograman, periode 2026/2027 Ganjil<br>Dosen: Dosen Demo (NIDN 9990000001)<br>Peserta: Mahasiswa Demo (NPM 9992300001) dan angkatan 2024 | Data kelas, dosen, dan peserta tersimpan | Setiap langkah memunculkan pesan sukses: "Kelas berhasil ditambahkan. Atur dosen pengampu dan pesertanya.", "Dosen pengampu berhasil ditambahkan.", "Peserta berhasil ditambahkan.", dan "8 mahasiswa angkatan 2024 ditambahkan." Detail kelas INF102 B menampilkan 1 dosen dan 9 peserta. | Berhasil |
| 7 | Rencana asesmen | Dosen menambahkan asesmen pada kelas yang diampu | Prasyarat: kelas INF102 B dari skenario 6.<br>1. Login sebagai Dosen.<br>2. Buka **Asesmen → Rencana Asesmen**, pilih INF102 B.<br>3. Klik **Tambah**, isi form, lalu **Simpan**. | Metode: Kuis<br>Nama: Kuis 1<br>Bobot: 10<br>Nilai maksimum: 20<br>Tanggal: 14-09-2026<br>Sub-CPMK0411: kontribusi 10 | Rencana asesmen tersimpan | Muncul pesan "Rencana asesmen berhasil ditambahkan." Kuis 1 (bobot 10, nilai maksimum 20) tersimpan dan tampil di daftar rencana asesmen INF102 B. | Berhasil |
| 8 | Pemetaan asesmen–Sub-CPMK | Dosen menghubungkan asesmen dengan Sub-CPMK | Prasyarat: kelas INF102 B dari skenario 6.<br>1. Tambah rencana asesmen.<br>2. Di bagian Sub-CPMK yang diukur, centang dua Sub-CPMK dan isi kontribusinya.<br>3. Klik **Simpan**. | Metode: UTS<br>Nama: UTS<br>Bobot: 30<br>Nilai maksimum: 100<br>Tanggal: 26-10-2026<br>Sub-CPMK0411: kontribusi 10<br>Sub-CPMK0412: kontribusi 20 | Hubungan tersimpan | Tersimpan dua hubungan UTS–Sub-CPMK dengan kontribusi 10 dan 20. Daftar asesmen menampilkan "Sub-CPMK0411 (10)" dan "Sub-CPMK0412 (20)". | Berhasil |
| 9 | Input nilai | Dosen memasukkan nilai peserta kelas | 1. Login sebagai Dosen.<br>2. Buka **Asesmen → Input Nilai**.<br>3. Pilih kelas INF102 A dan asesmen UTS.<br>4. Isi nilai dua peserta yang masih "Belum".<br>5. Klik **Simpan Nilai**. | Putra Wijaya (9992600001): 78<br>Qori Amalia (9992600002): 85,5 | Nilai tersimpan | Muncul pesan "Nilai UTS berhasil disimpan: 8 terisi, 0 belum diinput." Nilai 78 dan 85,5 tersimpan. Koma desimal diterima. | Berhasil |
| 10 | Input nilai | Dosen memasukkan nilai di atas nilai maksimum | 1. Login sebagai Dosen.<br>2. Buka **Input Nilai**, pilih kelas INF102 A dan asesmen Kuis 1.<br>3. Isi nilai Putra Wijaya.<br>4. Klik **Simpan Nilai**. | Nilai 25 untuk asesmen dengan nilai maksimum 20 | Ditolak / muncul pesan validasi | Nilai ditolak. Di bawah field muncul pesan "Nilai tidak boleh lebih dari nilai maksimum (20).", dan nilai lama di database tidak berubah. | Berhasil |
| 11 | Perhitungan capaian | Nilai asesmen tersedia lengkap | 1. Login sebagai Koprodi.<br>2. Buka **Laporan Capaian**.<br>3. Pilih Kurikulum 2021, periode 2025/2026 Genap, MK INF201 Struktur Data, lalu **Terapkan Filter**.<br>4. Buka tab Sub-CPMK, CPMK, dan CPL. | Kelas INF201 A: 8 peserta, keempat asesmen sudah dinilai semua | Capaian Sub-CPMK, CPMK, dan CPL terhitung | Rata-rata capaian kelas:<br>• Sub-CPMK0121 67,38; Sub-CPMK0211 67,42; Sub-CPMK0212 67,62<br>• CPMK012 67,38; CPMK021 67,52<br>• CPL01 67,38; CPL02 67,52<br>CPL03 dan CPL04 tampil "Belum ada nilai" karena tidak diukur INF201. | Berhasil |
| 12 | Dashboard | Koprodi membuka dashboard | 1. Login sebagai Koprodi.<br>2. Buka **Dashboard**. | Kurikulum OBE 2026 (bawaan), semua angkatan | Ringkasan capaian tampil | Kartu statistik tampil: 24 mahasiswa, 3 mata kuliah, 2 kelas aktif, 10 CPMK, dan 5 CPL. Ringkasan Capaian CPL tampil: 3 Tercapai, 1 Tidak Tercapai, 1 Belum ada nilai. | Berhasil |
| 13 | Filter laporan | Koprodi/Dosen memilih kriteria laporan | 1. Login sebagai Koprodi, buka **Laporan Capaian**, tab CPMK.<br>2. Pilih Kurikulum OBE 2026 dan periode 2026/2027 Ganjil.<br>3. Pilih MK INF101, lalu **Terapkan Filter**.<br>4. Kosongkan MK, pilih mahasiswa Mahasiswa Demo, lalu **Terapkan Filter**. | Filter mata kuliah: INF101 Matematika Diskrit<br>Filter mahasiswa: Mahasiswa Demo (9992300001) | Data tampil sesuai filter | Tanpa filter MK tampil 6 baris CPMK dari INF101 dan INF102.<br>Filter INF101 hanya menampilkan CPMK021 (69,97), CPMK022 (70,06), dan CPMK031 (69,78).<br>Filter Mahasiswa Demo hanya menghitung nilainya: CPMK021 85,25; CPMK022 91,71; CPMK031 93,00. | Berhasil |
| 14 | Laporan dosen | Dosen membuka laporan kelas yang diampu | 1. Login sebagai Dosen.<br>2. Buka **Laporan Capaian** (bawaan Kurikulum OBE 2026).<br>3. Lihat tab CPMK dan CPL.<br>4. Ketik id kelas INF101 A di alamat (`?kelas=...`). | Dosen Demo (9990000001), di kurikulum ini hanya mengampu INF102 A | Capaian kelas tersebut tampil | Tab CPMK hanya berisi CPMK INF102: CPMK011 67,38; CPMK041 66,69; CPMK042 67,62. Tab CPL hanya berisi CPL01 (67,38) dan CPL04 (67,49). Data kelas INF101 A milik dosen lain tidak tampil walaupun id-nya diketik di alamat. | Berhasil |
| 15 | Capaian mahasiswa | Mahasiswa membuka halaman capaian pribadi | 1. Login sebagai Mahasiswa Demo. Halaman Capaian Pembelajaran terbuka (periode 2026/2027 Ganjil, MK INF101).<br>2. Logout, lalu login sebagai mahasiswa lain dan buka halaman yang sama. | Mahasiswa Demo: NPM `9992300001` / `mahasiswa123`<br>Putra Wijaya: NPM `9992600001` / `mahasiswa123` | Hanya nilai dan capaian mahasiswa itu yang tampil | Tampil identitas Mahasiswa Demo (NPM 9992300001), nilai Kuis 1 41,5 dari 50, Tugas 1 90, UTS 86, dan UAS 93. Ringkasan: Sub-CPMK 89,30; CPMK 89,99; CPL kumulatif 90,63. Nama teman sekelas di INF101 A tidak tampil, dan Putra Wijaya tidak melihat data Mahasiswa Demo. | Berhasil |
| 16 | Tampilan mobile | Pengguna membuka setiap halaman di layar selebar 360px | 1. Isi data demo, build aset, dan jalankan aplikasi.<br>2. Jalankan `npm run cek:tampilan`.<br>3. Periksa tangkapan layar di `storage/app/tangkapan-layar/360/`.<br>4. (Manual) Buka halaman di HP atau DevTools mode perangkat 360px. | 51 halaman dari semua peran (Login, Pilih Peran, 31 halaman Admin, 8 Koprodi, 9 Dosen, 1 Mahasiswa) di lebar 360, 768, dan 1280 px | Halaman tampil utuh dan bisa dipakai tanpa geser horizontal (kecuali isi matriks) | Semua halaman tampil tanpa geser horizontal di tingkat halaman dan tanpa error JavaScript. Font input di mobile 16px, dan semua tombol, tautan ikon, serta lingkaran matriks berukuran minimal 44×44 px. Matriks digeser di dalam kartunya sendiri. Satu temuan diperbaiki: tombol Keluar di Pilih Peran semula hanya 42×20 px. | Berhasil |

### Catatan

- **Rumus capaian.** Kebenaran rumus perhitungan (rumus 1–6 dan contoh tabel APTIKOM) diuji
  terpisah oleh unit test `tests/Unit/CapaianServiceTest.php` (Uji 1–6, bagian 5.5 spesifikasi).
  Skenario 11 menguji bahwa hasilnya tampil di aplikasi.
- **Kasus tepi.** Setiap fitur juga punya test kasus tepi di file test fiturnya, mis. kode duplikat,
  bobot kontribusi tidak sama, dan mahasiswa terdaftar dua kali. Daftarnya ada di bagian 4.

## 4. Pemetaan skenario ke test

| No | Test skenario (`tests/Feature/SkenarioBlackBoxTest.php`) | Test fitur yang lebih rinci |
|---|---|---|
| 1 | `test_skenario_01_login_dengan_data_valid_diarahkan_ke_halaman_sesuai_hak_akses` | `Auth/LoginTest`, `Auth/PeranAktifTest` |
| 2 | `test_skenario_02_login_dengan_data_salah_ditolak_dengan_pesan` | `Auth/LoginTest` |
| 3 | `test_skenario_03_membuka_fitur_di_luar_kewenangan_ditolak` | `Auth/HakAksesTest`, `Admin/BerandaAdminTest` |
| 4 | `test_skenario_04_admin_menambahkan_data_master_kurikulum` | `Admin/DataMasterKurikulumTest`, `Admin/KodeUnikPerKurikulumTest`, `Admin/SubCpmkTest` |
| 5 | `test_skenario_05_admin_menyimpan_pemetaan_kurikulum` | `Admin/PemetaanKurikulumTest` |
| 6 | `test_skenario_06_admin_membuat_kelas_dan_menentukan_dosen_serta_peserta` | `Admin/PerkuliahanTest` |
| 7 | `test_skenario_07_dosen_menambahkan_rencana_asesmen_pada_kelas_yang_diampu` | `Dosen/RencanaAsesmenTest` |
| 8 | `test_skenario_08_dosen_menghubungkan_asesmen_dengan_sub_cpmk` | `Dosen/RencanaAsesmenTest` |
| 9 | `test_skenario_09_dosen_memasukkan_nilai_peserta_kelas` | `Dosen/InputNilaiTest` |
| 10 | `test_skenario_10_nilai_di_atas_nilai_maksimum_ditolak` | `Dosen/InputNilaiTest` |
| 11 | `test_skenario_11_capaian_terhitung_jika_nilai_asesmen_lengkap` | `CapaianDataContohTest`, `Unit/CapaianServiceTest` |
| 12 | `test_skenario_12_koprodi_membuka_dashboard_dan_ringkasan_capaian_tampil` | `LaporanCapaianTest` |
| 13 | `test_skenario_13_laporan_tampil_sesuai_filter` | `LaporanCapaianTest` |
| 14 | `test_skenario_14_dosen_membuka_laporan_kelas_yang_diampu` | `LaporanCapaianTest` |
| 15 | `test_skenario_15_mahasiswa_hanya_melihat_nilai_dan_capaian_miliknya` | `Mahasiswa/CapaianMahasiswaTest` |
| 16 | `test_skenario_16_semua_halaman_bisa_dibuka_dan_siap_untuk_layar_mobile` dan `npm run cek:tampilan` | – |

### Bukti bahwa test bisa mendeteksi kesalahan (uji mutasi)

Untuk memastikan test skenario tidak selalu lulus, lima aturan penting sengaja dirusak satu per satu,
lalu `SkenarioBlackBoxTest` dijalankan. Setiap aturan dikembalikan sesudahnya.

| Aturan yang dirusak | Test yang gagal |
|---|---|
| Middleware peran tidak menolak peran lain | Skenario 3 |
| Batas nilai maksimum pada validasi nilai dihapus | Skenario 10 |
| Nilai tidak dinormalisasi ke skala 0–100 (rumus 1) | Skenario 12, 13, 14, 15 |
| Laporan dosen tidak dibatasi kelas yang diampu | Skenario 14 |
| Halaman mahasiswa memuat kelas semua mahasiswa | Skenario 15 |

Skenario 11 tidak menangkap kesalahan normalisasi, karena semua asesmen INF201 bernilai maksimum 100
sehingga hasilnya sama. Kesalahan itu tertangkap oleh skenario 15 (Kuis 1 bernilai maksimum 50) dan
oleh unit test rumus.

## 5. Daftar tangkapan layar

Ambil versi desktop (lebar 1280 px) dan mobile (lebar 360 px) untuk setiap tangkapan layar. Di
Chrome, mode mobile bisa dibuka lewat DevTools (F12) → ikon perangkat (Ctrl+Shift+M) → lebar 360.

### 5.1 Bukti per skenario (diambil manual)

Tangkapan layar ini menunjukkan keadaan setelah aksi pengguna, jadi diambil manual dengan mengikuti
langkah di bagian 3. Kolom terakhir menyebut tangkapan layar halaman (bagian 5.2) yang bisa dipakai
langsung.

| No | Tangkapan layar yang perlu diambil | Sudah tersedia otomatis |
|---|---|---|
| 1 | Form login terisi data valid; halaman tujuan setiap peran; halaman Pilih Peran akun dua peran | `login`, `admin-beranda`, `koprodi-dashboard`, `dosen-dashboard`, `mahasiswa-capaian`, `pilih-peran` |
| 2 | Halaman login dengan pesan "Email/NPM/NIDN atau password salah." | – |
| 3 | Halaman 403 "Akses Ditolak" saat Dosen membuka `/admin` | `error-403` |
| 4 | Form Tambah CPL terisi; daftar CPL dengan pesan sukses dan baris CPL06 | `admin-cpl-form` (form kosong) |
| 5 | Matriks CPL – Profil Lulusan dengan lingkaran CPL04–PL02 tercentang; pesan sukses setelah disimpan. Di mobile, tunjukkan matriks yang digeser di dalam kartunya. | `admin-pemetaan-cpl-pl` (sebelum diubah) |
| 6 | Form Tambah Kelas terisi; detail kelas INF102 B dengan dosen, 9 peserta, dan pesan sukses | `admin-kelas-kuliah-form` (form kosong) |
| 7 | Form Tambah Rencana Asesmen (Kuis 1) terisi; daftar rencana asesmen INF102 B dengan pesan sukses | `dosen-rencana-asesmen-form` (form kosong) |
| 8 | Form asesmen UTS dengan dua Sub-CPMK dicentang dan kontribusinya; daftar asesmen yang menampilkan "Sub-CPMK0411 (10)" dan "Sub-CPMK0412 (20)" | – |
| 9 | Input nilai UTS INF102 A dengan dua peserta "Belum" (sebelum) dan pesan sukses (sesudah). Di mobile, tunjukkan kartu per mahasiswa dan tombol Simpan Nilai yang menempel di bawah. | `dosen-input-nilai-tabel` |
| 10 | Input nilai Kuis 1 dengan nilai 25 dan pesan "Nilai tidak boleh lebih dari nilai maksimum (20)." | – |
| 11 | Laporan Capaian INF201 periode 2025/2026 Genap pada tab Sub-CPMK, CPMK, dan CPL | – |
| 12 | Dashboard Koprodi dengan Ringkasan Capaian CPL | `koprodi-dashboard` |
| 13 | Laporan tab CPMK dengan filter MK INF101, dan dengan filter Mahasiswa Demo | – |
| 14 | Laporan Capaian akun Dosen pada tab CPMK dan CPL | `dosen-laporan` (tab CPMK) |
| 15 | Capaian Pembelajaran Mahasiswa Demo; halaman yang sama untuk Putra Wijaya | `mahasiswa-capaian` |
| 16 | Keluaran terminal `npm run cek:tampilan`; contoh beberapa halaman di lebar 360 px | Semua file di folder `360/` |

### 5.2 Tampilan setiap halaman (otomatis)

`npm run cek:tampilan` menyimpan tangkapan layar satu halaman penuh untuk setiap halaman di
`storage/app/tangkapan-layar/<lebar>/<nama>.png`. Gunakan folder `1280/` untuk desktop, `360/` untuk
mobile, dan `768/` untuk tablet. Folder ini tidak ikut di-commit; jalankan ulang skrip untuk
membuatnya kembali.

| No | Halaman | Akun | Nama file |
|---|---|---|---|
| 1 | Login | Tamu | `login` |
| 2 | Pilih Peran | `koprodi.dosen@simonev.test` | `pilih-peran` |
| 3 | Profil | Admin | `profil` |
| 4 | Ubah Password | Admin | `ubah-password` |
| 5 | Halaman 403 Akses Ditolak | Koprodi | `error-403` |
| 6 | Halaman 404 | Admin | `error-404` |
| 7 | Beranda Admin | Admin | `admin-beranda` |
| 8 | Program Studi | Admin | `admin-program-studi` |
| 9 | Kurikulum | Admin | `admin-kurikulum` |
| 10 | Evaluasi Kurikulum | Admin | `admin-evaluasi-kurikulum` |
| 11 | Profil Lulusan (daftar dan form) | Admin | `admin-profil-lulusan`, `admin-profil-lulusan-form` |
| 12 | Profesi | Admin | `admin-profesi` |
| 13 | CPL (daftar dan form) | Admin | `admin-cpl`, `admin-cpl-form` |
| 14 | Bahan Kajian | Admin | `admin-bahan-kajian` |
| 15 | Mata Kuliah (daftar dan detail) | Admin | `admin-mata-kuliah`, `admin-mata-kuliah-detail` |
| 16 | CPMK | Admin | `admin-cpmk` |
| 17 | Sub-CPMK (daftar dan detail) | Admin | `admin-sub-cpmk`, `admin-sub-cpmk-detail` |
| 18 | Metode Penilaian | Admin | `admin-metode-penilaian` |
| 19 | Dosen | Admin | `admin-dosen` |
| 20 | Mahasiswa | Admin | `admin-mahasiswa` |
| 21 | Pemetaan Kurikulum (4 matriks) | Admin | `admin-pemetaan-cpl-pl`, `admin-pemetaan-cpl-bk`, `admin-pemetaan-bk-mk`, `admin-pemetaan-cpmk-mk` |
| 22 | Periode Akademik | Admin | `admin-periode-akademik` |
| 23 | Kelas Kuliah (daftar, form, dan detail) | Admin | `admin-kelas-kuliah`, `admin-kelas-kuliah-form`, `admin-kelas-kuliah-detail` |
| 24 | Akun Pengguna (daftar dan form) | Admin | `admin-akun`, `admin-akun-form` |
| 25 | Dashboard Koprodi | Koprodi | `koprodi-dashboard` |
| 26 | Data Kurikulum (ringkasan, mata kuliah, pemetaan) | Koprodi | `koprodi-data-kurikulum`, `koprodi-data-kurikulum-mata-kuliah`, `koprodi-data-kurikulum-pemetaan` |
| 27 | Laporan Capaian (tab CPL, CPMK, Sub-CPMK) | Koprodi | `koprodi-laporan-cpl`, `koprodi-laporan-cpmk`, `koprodi-laporan-sub-cpmk` |
| 28 | Dashboard Dosen | Dosen | `dosen-dashboard` |
| 29 | Mata Kuliah Dosen (daftar dan detail) | Dosen | `dosen-mata-kuliah`, `dosen-mata-kuliah-detail` |
| 30 | Rencana Asesmen (pilih kelas, daftar, form) | Dosen | `dosen-rencana-asesmen-kelas`, `dosen-rencana-asesmen`, `dosen-rencana-asesmen-form` |
| 31 | Input Nilai (pilih kelas dan tabel nilai) | Dosen | `dosen-input-nilai`, `dosen-input-nilai-tabel` |
| 32 | Laporan Capaian Dosen | Dosen | `dosen-laporan` |
| 33 | Capaian Pembelajaran Mahasiswa | Mahasiswa | `mahasiswa-capaian` |

Halaman detail dan form asesmen diambil dari tautan pertama di daftarnya, mis. detail mata kuliah
pertama di daftar Mata Kuliah.
