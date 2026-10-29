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
