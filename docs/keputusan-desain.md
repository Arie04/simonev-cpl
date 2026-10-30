# Keputusan Desain SIMONEV-CPL

Catatan setiap keputusan desain beserta tanggal, alasan, dan dampaknya.

- KD-01 sampai KD-16 adalah isi awal dari bagian 13 `docs/spesifikasi.md`.
- KD-17 sampai KD-25 adalah hasil klarifikasi spesifikasi sebelum iterasi 1.
- KD-26 dan seterusnya adalah keputusan teknis saat pengerjaan.
- Keputusan yang kemudian diganti tidak dihapus, tetapi diberi status "Diganti oleh KD-xx" supaya riwayatnya tetap terlihat.

## Isi awal (bagian 13 spesifikasi)

### KD-01 · Tampilan memakai Blade, Tailwind CSS, Alpine.js, dan Chart.js
- **Tanggal:** 2026-10-30
- **Keputusan:** Antarmuka dibuat dengan Blade, Tailwind CSS v4, dan Alpine.js 3; grafik dengan Chart.js 4. Tidak memakai starter kit.
- **Alasan:** Breeze sudah tidak dikembangkan, sedangkan starter kit resmi Laravel memakai React, Vue, atau Livewire. Blade + Alpine.js cukup untuk interaksi sederhana dan mudah dijelaskan.
- **Dampak:** Login dan otorisasi dibuat sendiri secara sederhana (controller + `Auth::attempt`, middleware peran, Policy).

### KD-02 · Metode perhitungan mengikuti APTIKOM
- **Tanggal:** 2026-10-30
- **Keputusan:** Semua bobot dinyatakan dalam % nilai akhir mata kuliah. Capaian CPL = Σ skor / Σ skor maks (Tabel K APTIKOM), ditulis sebagai rata-rata tertimbang bertingkat asesmen → Sub-CPMK → CPMK → CPL. SKS tidak dipakai sebagai bobot.
- **Alasan:** Prodi mengacu pada Panduan Kurikulum APTIKOM Versi 2.0, Bab J. Rata-rata tertimbang bertingkat menghasilkan angka yang sama dengan cara APTIKOM, dan setiap angka bisa ditelusuri balik ke asesmen sumbernya.
- **Dampak:** Rumus hanya ada di `CapaianService` (iterasi 7) dan diuji dengan Uji 1–6 di bagian 5.5 spesifikasi.

### KD-03 · Dua ambang batas disimpan di `.env`
- **Tanggal:** 2026-10-30
- **Keputusan:** Ambang Batas Kelulusan Mahasiswa (AM) dan Ambang Batas Ketercapaian (AK) disimpan di `.env`, dibaca lewat `config/capaian.php`, dan berlaku global. `.env.example` memakai contoh APTIKOM (50.01 dan 85).
- **Alasan:** ERD tidak perlu berubah. Nilainya ditetapkan prodi dan diisi pengelola server.
- **Dampak:** Status Lulus/Tercapai selalu memakai ambang yang berlaku saat ini. Jika ambang kosong atau tidak valid, status ditampilkan "Ambang belum ditetapkan".

### KD-04 · Nilai kosong diabaikan
- **Tanggal:** 2026-10-30
- **Keputusan:** Asesmen yang belum diberi nilai tidak ikut dihitung: penyebut hanya memakai bobot yang punya nilai. Jika tidak ada nilai sama sekali, hasilnya null ("Belum ada nilai"), bukan 0.
- **Alasan:** Menghitung nilai kosong sebagai 0 akan menurunkan capaian mahasiswa yang nilainya memang belum diinput.
- **Dampak:** Mahasiswa dengan hasil null tidak ikut dihitung dalam persentase agregat.

### KD-05 · Mahasiswa yang mengulang memakai kelas periode terbaru
- **Status:** Diganti oleh KD-24.
- **Tanggal:** 2026-10-30
- **Keputusan:** Jika mahasiswa punya lebih dari satu kelas untuk MK yang sama, capaian CPL memakai kelas pada periode terbaru.
- **Alasan:** Hasil kelas terbaru paling mencerminkan kemampuan mahasiswa saat ini.
- **Dampak:** Satu MK hanya dihitung sekali untuk setiap mahasiswa.

### KD-06 · Periode akademik aktif
- **Tanggal:** 2026-10-30
- **Keputusan:** "Periode akademik aktif" adalah periode terbaru yang sudah memiliki kelas. Periode yang dibuat lebih awal tetapi belum berisi kelas tidak mengubah periode aktif.
- **Alasan:** ERD tidak punya kolom status aktif.
- **Dampak:** Kartu "periode akademik aktif" dan "jumlah kelas aktif" di dashboard dihitung dari data kelas.

### KD-07 · Lebih dari satu kurikulum
- **Tanggal:** 2026-10-30
- **Keputusan:** Sistem menangani kurikulum yang berlaku sekarang dan kurikulum berikutnya sekaligus. Kurikulum mahasiswa tidak disimpan; CPL mahasiswa mengikuti kurikulum dari MK yang ia ikuti. Kode-kode kurikulum unik per kurikulum, bukan unik global.
- **Alasan:** Arahan dosen pembimbing. Dalam satu periode, kelas dari kedua kurikulum bisa berjalan bersamaan.
- **Dampak:** Semua perhitungan dan pengelompokan memakai id, bukan kode. CPL dari kurikulum berbeda tidak pernah digabung.

### KD-08 · Perhitungan memakai bobot rencana asesmen kelas
- **Tanggal:** 2026-10-30
- **Keputusan:** Perhitungan memakai `rencana_asesmen_sub_cpmk.bobot_kontribusi` yang benar-benar dipakai di kelas. `bobot_sub_cpmk` dan `bobot_cpmk` di kurikulum hanya target untuk validasi dan tampilan.
- **Alasan:** Revisi bobot di kurikulum tidak boleh mengubah hasil kelas yang sudah lewat.
- **Dampak:** Jika rencana asesmen kelas tidak sesuai target, sistem menampilkan peringatan, bukan memblokir.

### KD-09 · Evaluasi kurikulum dikelola Admin
- **Tanggal:** 2026-10-30
- **Keputusan:** Evaluasi kurikulum dikelola Admin di menu Data Master (bagian dari KF-03); Koprodi hanya melihat.
- **Alasan:** Sesuai pembagian hak akses: Admin mengelola data master, Koprodi read-only.
- **Dampak:** Halaman evaluasi kurikulum dibuat di iterasi 2; Koprodi melihatnya lewat Data Kurikulum (iterasi 3).

### KD-10 · Login dengan email, NPM, atau NIDN
- **Tanggal:** 2026-10-30
- **Keputusan:** Satu isian login menerima email, NPM, atau NIDN. Tampilan memakai label "NPM" seperti mockup, sedangkan kolom database tetap `nim` sesuai ERD.
- **Alasan:** Mahasiswa dan dosen lebih hafal NPM/NIDN daripada email.
- **Dampak:** Login mencari pemilik identitas di `users.email`, lalu `mahasiswa.nim`, lalu `dosen.nidn`.

### KD-11 · Menu mengikuti use case dan KF
- **Tanggal:** 2026-10-30
- **Keputusan:** Menu tiap peran mengikuti use case dan KF (bagian 7.5 spesifikasi). Menu di mockup yang tidak ada di use case tidak dibuat.
- **Alasan:** Menjaga cakupan sistem sesuai proposal.
- **Dampak:** Admin tidak punya menu Monitoring, Laporan, dan Pengaturan; Koprodi tidak punya Perencanaan dan Rekapitulasi; Dosen tidak punya Evaluasi.

### KD-12 · Tata letak mockup dengan palet Unkhair
- **Tanggal:** 2026-10-30
- **Keputusan:** Tata letak mengikuti mockup proposal (Gambar 3.7–3.15). Warna memakai palet biru + kuning Unkhair (bagian 7.2 spesifikasi) dan font Plus Jakarta Sans.
- **Alasan:** Mockup berupa wireframe abu-abu; identitas visual diambil dari lambang, bendera, dan jas almamater Unkhair.
- **Dampak:** Semua warna didefinisikan sebagai token di satu tempat (`resources/css/app.css`).

### KD-13 · Angka memakai koma desimal
- **Tanggal:** 2026-10-30
- **Keputusan:** Angka ditampilkan dengan koma desimal dan dua angka di belakang koma ("78,40"), seperti penulisan di buku APTIKOM, berbeda dari mockup yang memakai titik.
- **Alasan:** Sesuai kaidah penulisan angka bahasa Indonesia dan buku acuan.
- **Dampak:** Input nilai menerima koma maupun titik, lalu dinormalisasi ke titik sebelum validasi dan penyimpanan.

### KD-14 · Akun hanya dibuat Admin
- **Tanggal:** 2026-10-30
- **Keputusan:** Tidak ada registrasi publik maupun reset password lewat email. Admin membuat akun dengan password awal dan mereset password lewat menu Akun Pengguna.
- **Alasan:** Sistem dipakai internal prodi dan tidak memakai layanan email.
- **Dampak:** Halaman login menampilkan "Lupa password? Hubungi Admin program studi". Tabel `password_reset_tokens` tidak dibuat (KD-18).

### KD-15 · Hanya dijalankan di laptop
- **Tanggal:** 2026-10-30
- **Keputusan:** Sistem dijalankan di laptop (localhost) untuk pengujian dan demo. Tidak ada fitur di luar KF (impor, ekspor, audit log) dan tidak ada panduan deploy ke server.
- **Alasan:** Cakupan skripsi.
- **Dampak:** README hanya menjelaskan instalasi lokal dan cara membuka aplikasi dari HP di Wi-Fi yang sama.

### KD-16 · Kolom Status mahasiswa tidak dibuat
- **Tanggal:** 2026-10-30
- **Keputusan:** Kolom Status pada mockup Data Mahasiswa tidak dibuat.
- **Alasan:** Kolom itu tidak ada di ERD.
- **Dampak:** Tabel Mahasiswa berkolom NPM, Nama Mahasiswa, Angkatan, dan Aksi.

## Hasil klarifikasi sebelum iterasi 1

### KD-17 · Test memakai PHPUnit dan database MySQL terpisah
- **Tanggal:** 2026-10-30
- **Keputusan:** Test memakai PHPUnit (bawaan Laravel) dan database MySQL `simonev_cpl_test`, bukan SQLite in-memory. `tests/TestCase.php` menghentikan test jika database yang terpakai bukan database test.
- **Alasan:** Hasil test dipakai untuk Bab 4, jadi harus berjalan di mesin basis data yang sama dengan aplikasi. Perilaku SQLite berbeda: collation MySQL menganggap `CPL01` dan `cpl01` sama, sedangkan SQLite tidak. Pengaman di `TestCase` mencegah test mengosongkan database pengembangan, misalnya saat config sedang di-cache.
- **Dampak:** Database test dibuat sekali dengan `sudo mysql` (lihat README). Nama method test memakai awalan `test_` diikuti kalimat bahasa Indonesia.

### KD-18 · Tabel bawaan Laravel yang dipakai
- **Tanggal:** 2026-10-30
- **Keputusan:** Dari tabel bawaan Laravel, hanya `sessions`, `cache`, dan `cache_locks` yang dibuat. `password_reset_tokens`, `jobs`, `job_batches`, dan `failed_jobs` dibuang, dan `QUEUE_CONNECTION=sync`. Kolom `sessions.user_id` bertipe UUID dengan index, tanpa foreign key constraint seperti bawaan Laravel.
- **Alasan:** Session disimpan di database, dan pembatas percobaan login memakai cache database. Tidak ada reset password lewat email (KD-14) dan tidak ada antrean, jadi tabel lainnya tidak akan pernah terisi.
- **Dampak:** Basis data berisi 29 tabel ERD, 3 tabel bawaan, dan tabel `migrations`. Baris `sessions` bersifat sementara sehingga tidak diberi constraint.

### KD-19 · Email boleh kosong untuk dosen dan mahasiswa
- **Tanggal:** 2026-10-30
- **Keputusan:** `users.email` boleh kosong (tetap unik jika diisi) untuk akun dosen dan mahasiswa. Akun yang tidak punya data dosen atau mahasiswa (mis. Admin) wajib punya email; ini divalidasi di aplikasi.
- **Alasan:** Dosen dan mahasiswa bisa login dengan NIDN/NPM, sehingga Admin tidak perlu mengisi email setiap mahasiswa.
- **Dampak:** Halaman Profil menampilkan "–" jika email kosong. MySQL mengizinkan banyak NULL pada kolom unik.

### KD-20 · Aturan cascade dan restrict
- **Tanggal:** 2026-10-30
- **Keputusan:** Baris pivot ikut terhapus jika pemiliknya dihapus, tetapi data yang dirujuk tidak bisa dihapus selama masih dipakai:
  - cascade dari kedua sisi untuk pemetaan kurikulum: `pemetaan_cpl_pl`, `pemetaan_cpl_bk`, `pemetaan_bk_mk`, `pemetaan_cpmk_mk`, `profil_lulusan_profesi`;
  - cascade hanya dari sisi pemilik untuk `user_roles` (users), `dosen_kelas` dan `peserta_kelas` (kelas_kuliah), `rencana_asesmen_sub_cpmk` (rencana_asesmen), `sub_cpmk_penilaian` (sub_cpmk);
  - semua FK lain restrict.
- **Alasan:** Jika cascade berlaku dari kedua sisi, menghapus Sub-CPMK akan diam-diam menghapus bobot kontribusi kelas lama dan mengubah capaian lama, padahal KD-08 melarangnya.
- **Dampak:** Sub-CPMK yang dipakai rencana asesmen, dosen yang masih mengampu kelas, mahasiswa yang masih terdaftar di kelas, peserta yang sudah punya nilai, dan pemetaan CPMK–MK yang sudah punya Sub-CPMK tidak bisa dihapus. Mulai iterasi 2, aplikasi memeriksa hal ini dan menampilkan pesan sebelum database menolak.

### KD-21 · Penempatan halaman yang belum tercantum di urutan iterasi
- **Tanggal:** 2026-10-30
- **Keputusan:** Profil, Ubah Password, halaman error, dan halaman sambutan sementara masuk iterasi 1. Beranda Admin masuk iterasi 2. Data Kurikulum Koprodi masuk iterasi 3. Dashboard Dosen dan halaman Mata Kuliah Dosen (KF-07) masuk iterasi 5.
- **Alasan:** Halaman-halaman ini ada di bagian 7.6 tetapi tidak disebut di bagian 11. Penempatannya mengikuti data yang dibutuhkan: menu akun dan middleware peran dibuat di iterasi 1, Data Kurikulum memakai ulang matriks pemetaan, dan Dashboard Dosen butuh data kelas dari iterasi 4.
- **Dampak:** Bagian 11 spesifikasi diperbarui. Halaman utama tiap peran di iterasi 1 hanya berisi sambutan dan diganti di iterasi 2 (Admin), 5 (Dosen), 8 (Koprodi), dan 9 (Mahasiswa).

### KD-22 · Kolom bahan kajian mengikuti Tabel 4 APTIKOM
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - `bobot_min` dan `bobot_max` adalah bobot tingkat kepentingan BK menurut CC2020: `unsignedTinyInteger`, boleh kosong, bilangan bulat ≥ 0, `bobot_min` ≤ `bobot_max`, tanpa batas maksimal 5.
  - `cs_core` dan `ka_core` adalah jumlah jam menurut CS2023: `unsignedSmallInteger`, boleh kosong. Null berarti BK tidak bersumber dari CS2023; 0 berarti memang 0 jam.
  - `referensi` berisi teks sumber ("CS2023", "CC2020").
  - `kategori_bk` berupa teks dengan saran "Kompetensi Utama", "Softskill", dan "Umum".
- **Alasan:** Mengikuti Tabel 4 buku kurikulum APTIKOM. BK22 Metodologi bernilai 2–6, dan sebagian BK (BK03, BK04, BK14) bersumber dari CC2020, bukan CS2023.
- **Dampak:** Tipe `bobot_min` dan `bobot_max` berbeda dari aturan umum `decimal(5,2)` untuk bobot. Kolom-kolom ini hanya dokumentasi kurikulum dan tidak dipakai dalam perhitungan capaian.

### KD-23 · Agregat CPL per periode
- **Tanggal:** 2026-10-30
- **Keputusan:** Jika filter periode dipilih, CPL dihitung hanya dari kelas pada periode itu. Jika "Semua periode", CPL dihitung kumulatif dari semua kelas. Laporan menampilkan keterangan "CPL dihitung dari kelas pada periode ini saja" atau "CPL kumulatif semua periode". Dashboard Koprodi selalu kumulatif, mengikuti filter kurikulum dan angkatan.
- **Alasan:** Arti filter periode menjadi sama untuk semua tab laporan, sedangkan dashboard memantau capaian keseluruhan.
- **Dampak:** Bagian 5.3 dan 7.6 spesifikasi diperbarui. `CapaianService` (iterasi 7) menerima periode sebagai filter opsional.

### KD-24 · Mahasiswa yang mengulang memakai kelas terbaru yang sudah bernilai
- **Tanggal:** 2026-10-30
- **Keputusan:** Untuk MK yang diulang, capaian CPL memakai kelas terbaru tempat mahasiswa itu sudah punya minimal satu nilai asesmen. Jika kelas terbaru belum punya nilai sama sekali, dipakai kelas sebelumnya yang sudah punya nilai. Begitu kelas terbaru punya nilai, kelas itu yang dipakai (nilai parsial dihitung sesuai KD-04). Keputusan ini menggantikan KD-05.
- **Alasan:** Agar capaian CPL tidak tiba-tiba hilang di awal semester saat mahasiswa mengulang.
- **Dampak:** Bagian 5.4 dan 13 spesifikasi diperbarui; aturan ini diuji di iterasi 7.

### KD-25 · Label baris laporan CPMK dan Sub-CPMK
- **Tanggal:** 2026-10-30
- **Keputusan:** Tanpa filter MK, tab CPMK dan Sub-CPMK menampilkan satu baris per pasangan dengan MK, berlabel "CPMK063 · MK04" atau "Sub-CPMK0811 · MK04" (memakai kode MK), diurutkan berdasarkan kode CPMK atau Sub-CPMK lalu kode MK. Jika satu MK dipilih, label cukup kode CPMK atau Sub-CPMK.
- **Alasan:** Rumus 5.2 menghitung CPMK per pasangan CPMK–MK, dan kode Sub-CPMK bisa dipakai di beberapa MK. Kode MK menjaga label grafik tetap pendek.
- **Dampak:** Bagian 7.6 spesifikasi diperbarui. Laporan (iterasi 8) mengelompokkan hasil per pasangan, bukan per kode.

## Keputusan teknis iterasi 1

### KD-26 · Peran aktif diperiksa di setiap request
- **Tanggal:** 2026-10-30
- **Keputusan:** Peran aktif disimpan di session (`peran_aktif`). Middleware `peran` memeriksa peran aktif, bukan semua peran milik user: pengguna Koprodi+Dosen yang sedang aktif sebagai Koprodi mendapat 403 di halaman dosen sampai ia berganti peran. Jika peran aktif dicabut Admin, pengguna diminta memilih peran lagi. Akun tanpa peran ditolak saat login.
- **Alasan:** Menu dan hak akses selalu sama dengan peran yang tampil di top bar, sehingga mudah dijelaskan dan diuji.
- **Dampak:** Setelah login, sistem selalu melewati langkah pilih peran. Jika peran hanya satu, langkah itu memilihkan peran secara otomatis.

### KD-27 · Gaya penulisan model Eloquent
- **Tanggal:** 2026-10-30
- **Keputusan:** Model memakai properti `$table`, `$primaryKey`, `$fillable`, dan `$hidden`, bukan atribut PHP `#[Fillable]`/`#[Hidden]` seperti di skeleton Laravel 13. Pivot yang punya PK sendiri dibuat sebagai model turunan `Pivot` dengan `HasUuids` dan dipasang lewat `->using()`. Setiap relasi menulis nama kolom FK secara eksplisit.
- **Alasan:** Seragam dengan spesifikasi bagian 4 dan dengan sebagian besar buku serta tutorial Laravel, sehingga mudah dijelaskan saat sidang. Nama PK `<tabel>_id` tidak cocok dengan tebakan bawaan Eloquent, jadi kolom FK perlu ditulis.
- **Dampak:** Setiap model berisi beberapa baris konfigurasi yang bentuknya sama.

### KD-28 · Zona waktu dibaca dari `APP_TIMEZONE`
- **Tanggal:** 2026-10-30
- **Keputusan:** `config/app.php` diubah agar membaca `APP_TIMEZONE` (Asia/Jayapura), karena Laravel 13 menulis `'timezone' => 'UTC'` langsung di file config.
- **Alasan:** Spesifikasi bagian 2 meminta zona waktu WIT.
- **Dampak:** Waktu disimpan dan ditampilkan dalam WIT.

### KD-29 · Nilai yang belum diinput tidak punya baris
- **Tanggal:** 2026-10-30
- **Keputusan:** `nilai_asesmen.nilai` wajib diisi. Nilai yang belum diinput berarti barisnya belum ada; mengosongkan nilai yang sudah tersimpan (iterasi 6) berarti menghapus barisnya.
- **Alasan:** Satu arti untuk "belum diinput" memudahkan perhitungan (KD-04) dan status Tersimpan/Belum di halaman Input Nilai.
- **Dampak:** Perhitungan cukup memeriksa ada tidaknya baris nilai.

### KD-30 · Tipe dan kewajiban kolom
- **Tanggal:** 2026-10-30
- **Keputusan:** Kode (`kode_*`, `nidn`, `nim`) berupa string pendek; `semester_periode` berupa enum Ganjil/Genap; tahun dan angka kecil (`tahun_berlaku`, `angkatan`, `semester`, `jumlah_sks`, `pertemuan_*`) berupa integer unsigned; bobot `decimal(5,2)` dan nilai `decimal(6,2)`. Kolom identitas dan bobot wajib, kolom keterangan (visi, materi, catatan, referensi, tanggal asesmen, dan sejenisnya) boleh kosong. Periode dan kelas tidak diberi UQ di database karena divalidasi di aplikasi (bagian 6 spesifikasi).
- **Alasan:** Spesifikasi hanya menyarankan sebagian tipe; sisanya dipilih agar validasi sederhana.
- **Dampak:** Rincian tiap kolom ada di file `database/migrations/`.

### KD-31 · Paket tambahan dan file bawaan skeleton
- **Tanggal:** 2026-10-30
- **Keputusan:** Paket tambahan hanya `blade-ui-kit/blade-heroicons` (ikon), `alpinejs`, dan `@fontsource/plus-jakarta-sans`. Terjemahan `lang/id/` ditulis sendiri. `CLAUDE.md` dan `AGENTS.md` bawaan skeleton Laravel 13 (panduan pemasangan Laravel Boost) tidak disalin, dan Laravel Boost tidak dipasang.
- **Alasan:** Ikon dan font disebut di spesifikasi. Menulis terjemahan sendiri lebih ringan daripada menambah paket terjemahan. File agen bawaan skeleton bertentangan dengan `CLAUDE.md` proyek.
- **Dampak:** Pesan validasi berbahasa Indonesia ada di `lang/id/validation.php`.

### KD-32 · Token warna dan menu sidebar
- **Tanggal:** 2026-10-30
- **Keputusan:** Palet warna bawaan Tailwind dimatikan (`--color-*: initial`), sehingga view hanya bisa memakai token warna bagian 7.2. Menu sidebar per peran ditulis di `config/menu.php` dan hanya berisi menu yang sudah berfungsi.
- **Alasan:** Mencegah warna di luar palet terpakai tanpa sengaja. Menu bertambah seiring iterasi tanpa tautan mati.
- **Dampak:** Nama kelas mengikuti nama token, mis. `bg-primary-900`, `text-text-muted`, dan `bg-bg`.

### KD-33 · Akun demo dengan password mudah
- **Tanggal:** 2026-10-30
- **Keputusan:** Lima akun demo: `admin`, `koprodi`, `dosen`, `koprodi.dosen`, dan `mahasiswa` `@simonev.test`. Password-nya bagian email sebelum `@` ditambah `123`, mis. `admin123`.
- **Alasan:** Mudah diketik saat mencoba aplikasi, dan tetap memenuhi aturan minimal 8 karakter di halaman Ubah Password.
- **Dampak:** Hanya untuk pengembangan lokal; daftar akun ada di README.

### KD-34 · Konfigurasi ambang dibuat sejak iterasi 1
- **Tanggal:** 2026-10-30
- **Keputusan:** `config/capaian.php` dan baris ambang di `.env.example` dibuat di iterasi 1 sebagai bagian setup, lalu dipakai `CapaianService` mulai iterasi 7.
- **Alasan:** `.env` sudah lengkap sejak awal, sehingga tidak perlu menyalin ulang `.env.example` di iterasi berikutnya.
- **Dampak:** Belum ada kode yang membaca ambang sampai iterasi 7.

## Hasil klarifikasi sebelum iterasi 2–10

Sebelum iterasi 2, sembilan hal yang ambigu diajukan beserta rekomendasinya (30 Oktober 2026). User
meminta pekerjaan dilanjutkan sampai semua iterasi selesai tanpa mengoreksi rekomendasi itu, jadi
rekomendasinya dipakai (KD-35 sampai KD-43). KD-44 sampai KD-48 adalah keputusan kecil yang
diumumkan bersamaan.

### KD-35 · Sub-CPMK dikelola di iterasi 3
- **Tanggal:** 2026-10-30
- **Keputusan:** Kelola Sub-CPMK, rincian penilaiannya (`sub_cpmk_penilaian`), dan ringkasan bobot di detail Mata Kuliah dipindah dari iterasi 2 ke iterasi 3. Ketiganya dikerjakan setelah matriks pemetaan CPMK–MK.
- **Alasan:** Sub-CPMK menempel ke pasangan CPMK–MK (`pemetaan_cpmk_mk`), dan pasangan itu baru bisa dibuat di matriks pemetaan.
- **Dampak:** Bagian 11 spesifikasi diperbarui. Iterasi 2 berisi seluruh data master selain Sub-CPMK.

### KD-36 · Aturan akun dan peran
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - Peran DOSEN dan MAHASISWA hanya untuk akun yang punya data dosen/mahasiswa. Akun seperti itu dibuat lewat Data Master Dosen/Mahasiswa.
  - Menu Akun Pengguna dipakai untuk membuat akun Admin/Koprodi (email wajib), mengatur peran, dan reset password.
  - Setiap akun minimal punya satu peran.
  - Akun hanya bisa dihapus di Akun Pengguna jika tidak punya data dosen/mahasiswa.
  - Menghapus data Dosen/Mahasiswa ikut menghapus akunnya. Jika akun itu masih punya peran lain, akun tetap ada dan hanya peran DOSEN/MAHASISWA yang dicabut.
  - Admin tidak bisa mencabut peran ADMIN miliknya atau menghapus akunnya sendiri.
- **Alasan:** Halaman Dosen dan Mahasiswa membutuhkan baris `dosen`/`mahasiswa`. Akun berperan Dosen tanpa data dosen tidak akan punya kelas. Larangan untuk akun sendiri mencegah sistem kehilangan Admin.
- **Dampak:** Bagian 6 dan 7.6 spesifikasi diperbarui. Koprodi yang juga dosen dibuat lewat Data Dosen, lalu diberi peran KOPRODI di Akun Pengguna.

### KD-37 · Satu kelas per MK per periode untuk setiap mahasiswa
- **Tanggal:** 2026-10-30
- **Keputusan:** Mahasiswa tidak bisa didaftarkan ke dua kelas untuk MK yang sama dalam satu periode.
- **Alasan:** Aturan MK yang diulang (KD-24) memilih kelas terbaru. Jika ada dua kelas dalam periode yang sama, kelas terbaru tidak punya jawaban tunggal.
- **Dampak:** Validasi di halaman detail kelas (iterasi 4).

### KD-38 · Aturan MK yang diulang juga untuk tab CPMK dan Sub-CPMK
- **Tanggal:** 2026-10-30
- **Keputusan:** Laporan tab CPMK dan Sub-CPMK memakai aturan kelas yang sama dengan CPL (KD-24). Setiap mahasiswa dihitung sekali per MK.
- **Alasan:** Jika dihitung per kelas, mahasiswa yang mengulang terhitung dua kali pada laporan "Semua periode".
- **Dampak:** Bagian 5.4 dan 13 spesifikasi diperbarui. `CapaianService` memilih kelas per mahasiswa per MK sebelum menghitung semua tab.

### KD-39 · Status dibandingkan dengan nilai yang dibulatkan dua desimal
- **Tanggal:** 2026-10-30
- **Keputusan:** Status Lulus (capaian ≥ AM) dan Tercapai (% Lulus ≥ AK) dibandingkan memakai angka yang sudah dibulatkan 2 desimal. Perhitungan berantai tetap presisi penuh.
- **Alasan:** Angka yang tampil dan statusnya selalu konsisten. Tidak ada capaian "50,01" yang berstatus Belum Lulus karena nilai sebenarnya 50,006.
- **Dampak:** Bagian 5.3 spesifikasi diperbarui. Diuji di iterasi 7.

### KD-40 · Kategori "Belum ada nilai" di ringkasan CPL dashboard
- **Tanggal:** 2026-10-30
- **Keputusan:** Donut dan tabel Ringkasan Capaian CPL di Dashboard Koprodi punya kategori ketiga, "Belum ada nilai" (abu), untuk CPL yang belum punya nilai sama sekali.
- **Alasan:** CPL seperti itu belum bisa dinilai Tercapai maupun Tidak Tercapai. Warna abu sesuai aturan 7.2 untuk status yang belum bisa dinilai.
- **Dampak:** Bagian 7.6 spesifikasi diperbarui.

### KD-41 · Arti `bobot_bk_mk` dan batas semua bobot
- **Tanggal:** 2026-10-30
- **Keputusan:** `bobot_bk_mk` adalah porsi BK dalam MK dalam persen (0–100), hanya dokumentasi kurikulum, dan jumlahnya per MK tidak diperiksa. Semua bobot persen lainnya divalidasi 0–100.
- **Alasan:** Spesifikasi tidak menyebut arti `bobot_bk_mk`, dan bagian 6 tidak punya aturan jumlah untuknya. Bobot lain adalah persen nilai akhir MK (bagian 5.1), jadi tidak mungkin lebih dari 100.
- **Dampak:** Bagian 4 dan 6 spesifikasi diperbarui.

### KD-42 · Isian teks bersaran dan batas semester/SKS
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - `jenis_mata_kuliah`, `level_bloom_mk`, dan `kategori_cpl` berupa teks bebas dengan saran di form (Wajib/Pilihan; C1–C6; Sikap/Pengetahuan/Keterampilan Umum/Keterampilan Khusus).
  - Semester mata kuliah 1–8 dan jumlah SKS 1–24.
- **Alasan:** Sama dengan `kategori_bk` (KD-22): saran mempercepat pengisian tanpa membatasi data asli prodi. 24 SKS adalah beban maksimal satu semester.
- **Dampak:** Bagian 6 spesifikasi diperbarui. Form memakai `<datalist>`.

### KD-43 · Data demo bertahap dan akun demo tambahan
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - Data contoh bagian 10 diisi bertahap mengikuti iterasi yang membutuhkannya: data master, pemetaan dan Sub-CPMK, kelas dan peserta, rencana asesmen, lalu nilai.
  - Dosen dan mahasiswa contoh selain lima akun demo utama tidak punya email. Mereka login dengan NIDN/NPM dan password `dosen123` atau `mahasiswa123`.
- **Alasan:** Setiap PR bisa dicoba dengan data yang cukup. Password tetap mudah diketik (KD-33).
- **Dampak:** Bagian 10 spesifikasi diperbarui. Daftar data contoh ada di README.

### KD-44 · Orientasi matriks pemetaan
- **Tanggal:** 2026-10-30
- **Keputusan:** Baris matriks adalah sisi kiri nama pemetaan dan kolomnya sisi kanan: CPL × Profil Lulusan, CPL × Bahan Kajian, Bahan Kajian × Mata Kuliah, CPMK × Mata Kuliah.
- **Alasan:** Satu aturan yang sama untuk keempat matriks, sehingga mudah dibaca dan dijelaskan.
- **Dampak:** Matriks yang lebar bisa digeser horizontal, dengan kolom pertama dan baris header menempel (bagian 7.4).

### KD-45 · "Periode terbaru" di Beranda Admin dan Dashboard Dosen
- **Tanggal:** 2026-10-30
- **Keputusan:** Kartu kelas di Beranda Admin dan Dashboard Dosen memakai periode aktif, yaitu periode terbaru yang sudah memiliki kelas (bagian 13).
- **Alasan:** Satu definisi periode untuk semua halaman. Periode yang baru dibuat tetapi belum berisi kelas tidak membuat kartu kosong.
- **Dampak:** `PeriodeAkademik::aktif()` dipakai di semua halaman.

### KD-46 · CPL di laporan Dosen hanya dari kelas yang diampu
- **Tanggal:** 2026-10-30
- **Keputusan:** Tab CPL di laporan Dosen dihitung hanya dari kelas yang ia ampu, yaitu kontribusi MK-nya ke CPL (KF-14).
- **Alasan:** Dosen hanya boleh melihat data kelasnya sendiri (bagian 3 dan 6).
- **Dampak:** Laporan Dosen dan Koprodi memakai service yang sama dengan cakupan kelas berbeda.

### KD-47 · Penyebut saat nilai belum lengkap
- **Tanggal:** 2026-10-30
- **Keputusan:** BSub dan BCPMK (rumus 3 dan 4) dihitung per mahasiswa, hanya dari bobot kontribusi asesmen yang sudah punya nilai.
- **Alasan:** Bagian 5.4 meminta penyebut hanya memakai bobot yang punya nilai. Dengan cara ini hasilnya tetap sama dengan Σ skor / Σ skor maks APTIKOM pada asesmen yang sudah dinilai.
- **Dampak:** Bagian 5.4 spesifikasi diperjelas. Diuji di iterasi 7 dengan kasus nilai sebagian kosong.

### KD-48 · Nilai maksimum tidak bisa diturunkan di bawah nilai yang ada
- **Tanggal:** 2026-10-30
- **Keputusan:** Saat rencana asesmen diubah, `nilai_maksimum` tidak boleh lebih kecil dari nilai tertinggi yang sudah diinput untuk asesmen itu.
- **Alasan:** Aturan 0 ≤ nilai ≤ `nilai_maksimum` (bagian 6) harus tetap berlaku untuk nilai yang sudah tersimpan.
- **Dampak:** Bagian 6 spesifikasi diperbarui. Validasi ada di iterasi 5.

### KD-49 · PR iterasi 2–10 di-merge oleh Claude
- **Tanggal:** 2026-10-30
- **Keputusan:** Atas permintaan user, PR setiap iterasi di-merge sendiri ke `main` setelah semua test lulus dan Pint bersih, lalu pekerjaan berlanjut ke iterasi berikutnya.
- **Alasan:** User ingin semua iterasi selesai dalam satu rangkaian kerja.
- **Dampak:** Branch dan PR tetap dibuat per iterasi (bagian 15), sehingga riwayat dan catatannya bisa dibaca belakangan.

## Keputusan teknis iterasi 2

### KD-50 · CPL milik CPMK yang sudah dipetakan tidak bisa diganti
- **Tanggal:** 2026-10-30
- **Keputusan:** CPL sebuah CPMK hanya bisa diganti selama CPMK itu belum dipetakan ke mata kuliah.
- **Alasan:** Jika CPMK yang sudah dipakai kelas dipindah ke CPL lain, capaian CPL kelas yang sudah berjalan ikut berubah diam-diam. Hal ini bertentangan dengan prinsip KD-08.
- **Dampak:** Form CPMK menampilkan pesan "CPL tidak bisa diganti karena CPMK ini sudah dipetakan ke mata kuliah."

### KD-51 · Pilihan kurikulum diingat selama sesi
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - Dropdown Kurikulum di halaman data master menyimpan pilihannya di session, sehingga tetap sama saat berpindah halaman. Bawaannya kurikulum terbaru.
  - Data baru masuk ke kurikulum terpilih lewat isian tersembunyi.
  - Kurikulum data lama tidak bisa dipindah lewat form ubah.
- **Alasan:** Admin biasanya mengisi satu kurikulum sampai selesai. Memindah data antarkurikulum akan merusak pemetaan dan capaian.
- **Dampak:** Method `kurikulumTerpilih()` di `app/Http/Controllers/Controller.php` dipakai semua controller data kurikulum.

### KD-52 · Pemeriksaan sebelum hapus ada di model
- **Tanggal:** 2026-10-30
- **Keputusan:** Setiap model yang bisa dihapus punya method `alasanTidakBisaDihapus()`. Method ini mengembalikan pesan jika data masih dipakai, atau `null` jika boleh dihapus. Controller menampilkan pesan itu sebagai toast. Foreign key restrict di database tetap menjadi pengaman terakhir.
- **Alasan:** Pesannya lebih jelas daripada error database, dan aturannya ada di satu tempat per model sehingga mudah dijelaskan.
- **Dampak:** Menghapus kurikulum, CPL, MK, CPMK, metode penilaian, dosen, mahasiswa, atau akun yang masih dipakai ditolak dengan pesan yang menyebut alasannya.

### KD-53 · Komponen tabel, pencarian, dan modal hapus bersama
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - Tabel daftar memakai komponen `x-tabel`, `x-tabel.baris`, `x-tabel.sel`, dan `x-tabel.aksi`. Setiap sel membawa `data-label`, sehingga di layar < 768px baris tampil sebagai kartu.
  - Pencarian memakai trait `BisaDicari` dengan daftar kolom `$kolomPencarian` di tiap model.
  - Modal konfirmasi hapus dipasang sekali di layout dan dibuka lewat event Alpine `konfirmasi-hapus`.
  - Setiap halaman daftar punya satu form untuk tambah dan ubah (`form.blade.php`).
- **Alasan:** Tiga belas halaman data master berbentuk sama. Komponen dan trait mencegah duplikasi (bagian 14 spesifikasi) tanpa pola yang berlebihan.
- **Dampak:** Halaman daftar di iterasi berikutnya cukup memakai komponen yang sama.

## Keputusan teknis iterasi 3

### KD-54 · Pasangan CPMK–MK milik Sub-CPMK yang sudah dipakai tidak bisa diganti
- **Tanggal:** 2026-10-30
- **Keputusan:** Pasangan CPMK–MK sebuah Sub-CPMK hanya bisa diganti selama Sub-CPMK itu belum dipakai rencana asesmen. Sub-CPMK yang sudah dipakai juga tidak bisa dihapus, dan pasangan CPMK–MK yang sudah punya Sub-CPMK tidak bisa dilepas dari matriks.
- **Alasan:** Ketiganya akan mengubah capaian kelas yang sudah berjalan secara diam-diam (KD-08, KD-20).
- **Dampak:** Form dan matriks menampilkan pesan yang menyebut alasannya.

### KD-55 · Matriks pemetaan disimpan sekaligus
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - Satu matriks disimpan sekaligus dengan `sync()`: sel yang dicentang dihubungkan dan sisanya dilepas.
  - Isian bobot hanya aktif (dan ikut terkirim) untuk sel yang terhubung.
  - Class `App\Services\MatriksPemetaan` menyusun data matriks untuk halaman Admin dan Koprodi.
- **Alasan:**
  - Admin melihat dan mengubah seluruh hubungan di satu layar, sesuai Gambar 3.10.
  - Mengirim bobot hanya untuk sel terhubung menjaga jumlah isian tetap kecil, di bawah batas `max_input_vars` PHP.
- **Dampak:** Komponen `x-matriks-pemetaan` dipakai dalam dua mode: bisa diubah dan read-only.

### KD-56 · Data Kurikulum Koprodi berbentuk tab
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - Halaman Data Kurikulum Koprodi berisi tab Ringkasan, Evaluasi, Profil Lulusan, CPL, Bahan Kajian, Mata Kuliah, CPMK, Sub-CPMK, dan Pemetaan.
  - Setiap tab hanya memuat datanya sendiri.
  - Tab Mata Kuliah menampilkan ringkasan bobot tiap MK yang bisa dibuka-tutup.
- **Alasan:** Satu halaman read-only dengan pemilih kurikulum mengikuti bagian 7.6.12, tanpa memuat semua data sekaligus.
- **Dampak:** Ringkasan bobot MK memakai komponen yang sama dengan detail Mata Kuliah di halaman Admin.

## Keputusan teknis iterasi 4

### KD-57 · Mata kuliah dan periode kelas terkunci setelah kelas terisi
- **Tanggal:** 2026-10-30
- **Keputusan:** Mata kuliah dan periode sebuah kelas hanya bisa diganti selama kelas itu belum punya peserta dan rencana asesmen. Nama kelas tetap bisa diganti.
- **Alasan:**
  - Sub-CPMK pada rencana asesmen harus milik MK kelas itu (bagian 6).
  - Aturan satu kelas per MK per periode (KD-37) dicek saat peserta ditambahkan. Mengganti MK atau periode belakangan bisa diam-diam melanggar keduanya.
- **Dampak:** Form ubah kelas menampilkan pesan jika penggantian ditolak.

### KD-58 · Pilihan periode diingat selama sesi
- **Tanggal:** 2026-10-30
- **Keputusan:** Dropdown Periode Akademik di halaman Kelas Kuliah menyimpan pilihannya di session, seperti dropdown kurikulum (KD-51). Bawaannya periode aktif, atau periode terbaru jika belum ada kelas sama sekali.
- **Alasan:** Admin biasanya menyiapkan kelas satu periode sampai selesai.
- **Dampak:** Method `periodeTerpilih()` di `app/Http/Controllers/Controller.php`. Kelas baru otomatis memakai periode terpilih.

### KD-59 · Dua cara menambah peserta
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - Peserta ditambahkan lewat pencarian satu mahasiswa (NPM atau nama) atau sekaligus per angkatan.
  - Penambahan per angkatan melewati mahasiswa yang sudah mengikuti MK itu pada periode yang sama, lalu melaporkan jumlah yang ditambahkan dan dilewati.
- **Alasan:** Mengikuti bagian 7.6.6 tanpa memblokir seluruh angkatan hanya karena beberapa mahasiswa sudah terdaftar di kelas paralel (KD-37).
- **Dampak:** Pesan sukses menyebut jumlah mahasiswa yang ditambahkan dan yang dilewati.

## Keputusan teknis iterasi 5

### KD-60 · Hak akses dosen lewat Policy
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - `KelasKuliahPolicy::kelola` mengizinkan sebuah kelas hanya untuk dosen yang terdaftar di `dosen_kelas` kelas itu.
  - `MataKuliahPolicy::lihatSebagaiDosen` mengizinkan sebuah MK hanya untuk dosen yang pernah atau sedang mengampu kelasnya.
  - Controller memanggil `Gate::authorize(...)`, dan Form Request memakai `can(...)` di `authorize()`.
- **Alasan:** Bagian 6 meminta pembatasan dosen dicek lewat Policy. Middleware `peran:DOSEN` hanya memastikan peran aktif, bukan kepemilikan kelas.
- **Dampak:** Dosen yang membuka kelas atau MK yang tidak diampunya mendapat 403.

### KD-61 · Form rencana asesmen dengan bobot kontribusi per Sub-CPMK
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - Form asesmen menampilkan semua Sub-CPMK milik MK kelas itu. Setiap Sub-CPMK yang dicentang diberi bobot kontribusi, dikirim sebagai `kontribusi[id Sub-CPMK]`.
  - Jumlah kontribusi dihitung langsung di browser (Alpine) sebagai bantuan. Pemeriksaan yang memblokir tetap di server (`RencanaAsesmenRequest`).
- **Alasan:** Bagian 6 meminta Σ bobot kontribusi = bobot asesmen diblokir karena keduanya diisi di form yang sama. Penghitung langsung membantu dosen sebelum menyimpan.
- **Dampak:** Asesmen tanpa Sub-CPMK, atau dengan Sub-CPMK dari MK lain, ditolak.

### KD-62 · Matriks bobot dan peringatan kelas dihitung di `MatriksBobotKelas`
- **Tanggal:** 2026-10-30
- **Keputusan:** Class `App\Services\MatriksBobotKelas` menyusun matriks Sub-CPMK × asesmen beserta tiga peringatan bagian 6:
  - Σ bobot asesmen ≠ 100;
  - Σ kontribusi per Sub-CPMK ≠ bobot_sub_cpmk;
  - metode asesmen tidak termasuk metode yang direncanakan di `sub_cpmk_penilaian`.
- **Alasan:** Peringatan ini menyangkut validasi bobot, bukan perhitungan capaian. Karena itu tempatnya di luar CapaianService, tetapi tetap di satu class supaya tidak tersebar di view.
- **Dampak:** Revisi target bobot di kurikulum langsung memunculkan peringatan di kelas yang sudah berjalan (Uji 5), tanpa mengubah rencana asesmen kelas itu.

## Keputusan teknis iterasi 6

### KD-63 · Filter bertingkat Input Nilai
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - Dropdown Mata Kuliah → Periode → Kelas → Rencana Asesmen memuat ulang halaman setiap kali diganti (form GET). Isinya hanya kelas yang diampu dosen.
  - Pilihan yang tidak cocok dengan tingkat di atasnya dikosongkan. Tingkat yang hanya punya satu pilihan dipilih otomatis.
  - Tautan langsung cukup membawa `?kelas=` (dan `?asesmen=`), lalu mata kuliah dan periodenya mengikuti kelas itu.
- **Alasan:** Tanpa API JavaScript, sesuai keputusan kecil sebelum iterasi 2. Pilihan otomatis mengurangi klik saat dosen hanya mengampu satu kelas.
- **Dampak:** Dashboard Dosen dan halaman Rencana Asesmen punya tautan langsung ke Input Nilai.

### KD-64 · Nilai disimpan sekaligus
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - Seluruh nilai satu asesmen dikirim dalam satu form `nilai[id peserta_kelas]`.
  - Jika satu nilai tidak valid, Form Request menolak seluruhnya: tidak ada yang disimpan, dan baris yang salah ditandai merah.
  - Isian kosong menghapus nilai yang tersimpan (KD-29).
  - Halaman memberi peringatan jika pengguna meninggalkannya sebelum menyimpan.
- **Alasan:** Mengikuti bagian 7.6.8 dan 7.4.
- **Dampak:** `RencanaAsesmen::simpanNilai()` menyimpan dalam satu transaksi.

## Keputusan teknis iterasi 7

### KD-65 · Bentuk CapaianService
- **Tanggal:** 2026-10-30
- **Keputusan:** `CapaianService` bekerja pada baris `peserta_kelas` yang dimuat bersama relasinya (`RELASI_PESERTA`). Method publiknya:
  - `hitungCapaianPeserta()` (rumus 1–3 dan 5);
  - `hitungCapaianCpl()` (rumus 4);
  - `pilihKelasPerMataKuliah()` (aturan mengulang);
  - `capaianPerItem()` (capaian setiap mahasiswa per item untuk laporan);
  - `rekap()`, `lulus()`, `tercapai()`, dan `ambang()`.
  Hasil tiap item disimpan per id (`sub_cpmk_id`, `pemetaan_cpmk_mk_id`, `cpl_id`), bukan per kode.
- **Alasan:**
  - Satu tempat untuk semua rumus (bagian 5.4 dan 14).
  - Perhitungan dilakukan langsung tanpa cache, dengan eager loading supaya tidak ada query berulang.
  - Pengelompokan per id mencegah CPL dua kurikulum tergabung.
- **Dampak:** Laporan, dashboard, dan halaman mahasiswa memanggil method yang sama dengan cakupan peserta yang berbeda. Filter periode dan cakupan dosen diterapkan pada daftar peserta sebelum dihitung.

### KD-66 · Unit test CapaianService memakai database test
- **Tanggal:** 2026-10-30
- **Keputusan:**
  - Uji 1–6 ditulis di `tests/Unit/CapaianServiceTest.php`. Test ini memakai `Tests\TestCase` dan database `simonev_cpl_test`, karena service bekerja dengan model Eloquent.
  - Data uji disusun dengan trait `Tests\Support\MembangunDataCapaian`.
- **Alasan:** Data uji sama persis dengan bagian 5.5, dan jalur query yang dipakai sama dengan aplikasi.
- **Dampak:** Trait pembantu yang sama dipakai lagi oleh feature test laporan (iterasi 8).
