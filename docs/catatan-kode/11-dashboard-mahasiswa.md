# 11 · Dashboard Mahasiswa (revisi)

Catatan revisi setelah iterasi 10. Dibaca setelah [09-capaian-mahasiswa.md](09-capaian-mahasiswa.md)
dan [10-pengujian.md](10-pengujian.md). Keputusannya dicatat sebagai KD-74, KD-75, dan KD-76 di
[keputusan-desain.md](../keputusan-desain.md).

## 1. Tujuan dan kebutuhan yang dipenuhi

Sebelum revisi ini, menu Mahasiswa hanya berisi Capaian Pembelajaran, jadi sidebar-nya tidak
berguna. Revisi ini menambah **Dashboard Mahasiswa**, halaman pertama setelah login, yang meringkas
capaian pembelajaran dalam grafik interaktif:

- **KF-15:** mahasiswa melihat capaian Sub-CPMK, CPMK, dan CPL miliknya. Dashboard meringkas capaian
  CPL, lalu menautkan ke halaman Capaian Pembelajaran untuk rincian per mata kuliah.
- **Bagian 5.3:** CPL mahasiswa bisa dipantau setiap tingkat sampai lulus, ditunjukkan grafik
  Perkembangan CPL per Periode.
- **Bagian 5.4:** CPL dari kurikulum berbeda tidak digabung, dan MK yang diulang memakai kelas
  terbaru yang sudah bernilai.
- **Bagian 6:** mahasiswa hanya bisa melihat datanya sendiri.
- **Bagian 7.2 dan 7.4:** warna status, label angka, dan tampilan mobile.

## 2. File yang dibuat atau diubah

| File | Peran |
|---|---|
| `routes/web.php` | Route `mahasiswa.dashboard` di `/mahasiswa`; Capaian Pembelajaran pindah ke `/mahasiswa/capaian` |
| `config/menu.php` | Menu Mahasiswa: Dashboard · Capaian Pembelajaran |
| `app/Http/Controllers/BerandaController.php` | Halaman utama Mahasiswa sekarang Dashboard |
| `app/Http/Controllers/Mahasiswa/DashboardMahasiswaController.php` | Controller tipis: ambil mahasiswa dari akun yang login, panggil service |
| `app/Services/DashboardMahasiswa.php` | Menyusun ringkasan, rincian CPL, perkembangan per periode, dan daftar MK dari `CapaianService` |
| `resources/views/mahasiswa/dashboard.blade.php` | Tampilan dashboard: kartu ringkasan, dua grafik, rincian CPL, dan tabel |
| `resources/js/app.js` | Komponen Alpine `dashboardMahasiswa` (dua grafik Chart.js) dan fungsi bersama `pluginLabelAngka()` |
| `tests/Feature/Mahasiswa/DashboardMahasiswaTest.php` | 16 test dashboard |
| `tests/Feature/Auth/*`, `tests/Feature/SkenarioBlackBoxTest.php` | Halaman utama Mahasiswa kini Dashboard; skenario 15 dan 16 juga memeriksa dashboard |
| `scripts/cek-tampilan.mjs` | Pengecekan tampilan ikut membuka dashboard (Mahasiswa Demo dan Hendra Kurniawan) |
| `docs/pengujian/black-box.md` | Hasil skenario 1, 15, dan 16, jumlah test, uji mutasi, dan daftar tangkapan layar |

## 3. Alur kerja

```mermaid
sequenceDiagram
    actor M as Mahasiswa
    participant C as DashboardMahasiswaController
    participant D as DashboardMahasiswa
    participant S as CapaianService
    participant V as dashboard.blade.php + app.js
    M->>C: GET /mahasiswa?kurikulum=…
    C->>C: $mahasiswa = user()->mahasiswa (tanpa id di alamat)
    C->>D: susun(mahasiswa, kurikulum)
    D->>D: peserta_kelas milik mahasiswa ini, lalu saring ke kurikulum terpilih
    D->>S: pilihKelasPerMataKuliah() lalu hitungCapaianCpl() (rumus 4)
    D->>S: hitungCapaianPeserta() per kelas → CPMK penyusun setiap CPL
    D->>S: untuk setiap periode P: hitungCapaianCpl(kelas sampai P)
    D-->>C: ringkasan, daftarCpl, perkembangan, daftarMataKuliah
    C-->>V: view + ambang
    V-->>M: HTML; Alpine membuat grafik Chart.js
    M->>V: klik batang / tombol CPL
    V-->>M: rincian CPL berganti, garis CPL itu ditebalkan
```

Klik di grafik tidak memanggil server lagi. Rincian semua CPL sudah ada di HTML, dan Alpine hanya
menampilkan rincian CPL yang dipilih.

## 4. Penjelasan potongan kode penting

### a. Semua isi dihitung dalam satu kurikulum

```php
$kurikulum = $daftarKurikulum->firstWhere('kurikulum_id', $kurikulumId) ?? $daftarKurikulum->first();

$pesertaKurikulum = $daftarPeserta
    ->filter(fn (PesertaKelas $peserta) => $peserta->kelasKuliah->mataKuliah->kurikulum_id === $kurikulum?->kurikulum_id)
    ->values();
```

- **Pilihan kurikulum.** `$daftarKurikulum` hanya berisi kurikulum dari mata kuliah yang diikuti,
  urut dari yang terbaru. Jika id kurikulum di alamat bukan salah satunya, `firstWhere` menghasilkan
  `null` dan dipakai kurikulum terbaru. Jadi mengetik id kurikulum lain di alamat tidak membuka
  datanya.
- **Kelas kurikulum terpilih.** Kelas yang dipakai hanya dari kurikulum terpilih, karena CPL01
  kurikulum 2021 dan CPL01 kurikulum 2026 adalah CPL yang berbeda (bagian 5.4).

### b. CPMK penyusun setiap CPL

```php
$daftarCapaian = $kelasTerpilih->map(fn (PesertaKelas $peserta) => [
    'kelas' => $peserta->kelasKuliah,
    'cpmk' => $this->capaian->hitungCapaianPeserta($peserta)['cpmk'],
]);
...
$penyusun[$cpmk['cpl_id']][] = [
    'kode_cpmk' => ..., 'kode_mata_kuliah' => ...,
    'capaian' => $cpmk['nilai'],
    'bobot' => $cpmk['bobot'],
];
```

- **Asal datanya.** `hitungCapaianPeserta()` sudah menghasilkan capaian CPMK (rumus 3) beserta bobot
  BCPMK dan `cpl_id`-nya. Di sini hasil itu hanya dikelompokkan per CPL.
- **Hubungan dengan angka CPL.** Rumus 4 menghitung CPL sebagai rata-rata tertimbang dari pasangan
  ini: Σ(capaian × bobot) / Σ bobot. Test `test_rincian_cpl_menampilkan_cpmk_penyusun...` memastikan
  hasilnya sama dengan angka CPL di dashboard.
- **Satu query.** Kode CPMK diambil dengan satu query `whereIn` untuk semua pasangan, bukan satu
  query per kelas.

### c. Perkembangan per periode

```php
$capaianPerPeriode = $daftarPeriode->map(function (PeriodeAkademik $periode) use ($pesertaKurikulum) {
    $sampaiPeriodeIni = $pesertaKurikulum->filter(
        fn (PesertaKelas $peserta) => $peserta->kelasKuliah->periodeAkademik->urutan() <= $periode->urutan(),
    );

    return $this->capaian->hitungCapaianCpl($this->capaian->pilihKelasPerMataKuliah($sampaiPeriodeIni));
});
```

- **Titik periode P.** Kelas yang dipakai adalah semua kelas sampai periode P. Hasilnya adalah CPL
  kumulatif "pada akhir periode P".
- **Aturan mengulang.** `pilihKelasPerMataKuliah()` tetap dipakai. Jika mahasiswa mengulang INF101
  dan kelas barunya belum bernilai, titik periode itu masih memakai kelas lama.
- **Urutan periode.** `urutan()` menghasilkan kunci seperti `"2026/2027-1"` (Ganjil) dan
  `"2026/2027-2"` (Genap), jadi periode bisa dibandingkan sebagai teks.

### d. Satu komponen Alpine untuk dua grafik

```js
Alpine.data('dashboardMahasiswa', (data) => {
    let grafikCpl = null;
    let grafikPerkembangan = null;

    return {
        terpilih: data.terpilih,
        pilih(indeks) {
            this.terpilih = indeks;
            grafikCpl?.update();
            ...
        },
    };
});
```

- **Satu sumber pilihan.** `terpilih` (urutan CPL yang dipilih) dipakai bersama oleh grafik batang,
  tombol kode CPL, kartu Rincian (`x-show="terpilih === 0"`), grafik perkembangan, dan baris tabel
  yang disorot.
- **Objek Chart di luar data Alpine.** Data Alpine dibungkus proxy agar reaktif. Jika objek Chart.js
  ikut dibungkus, grafik menjadi lambat atau error. Karena itu objek Chart disimpan di variabel biasa.
- **Opsi yang dihitung saat digambar.** `borderWidth: (konteks) => konteks.dataIndex === komponen.terpilih ? 2 : 0`
  adalah opsi scriptable Chart.js. Setelah `update()`, batang CPL terpilih diberi garis tepi.

### e. Klik di seluruh kolom, bukan hanya di batang

```js
interaction: { mode: 'index', intersect: false, axis: sumbuItem },
onClick: (event, daftarElemen) => {
    if (daftarElemen.length > 0) {
        komponen.pilih(daftarElemen[0].index);
    }
},
```

- **Seluruh kolom.** Dengan `intersect: false`, klik atau hover di mana pun pada kolom satu CPL tetap
  mengenai CPL itu, jadi batang yang pendek pun mudah diklik.
- **Kolom tanpa nilai.** Kolom CPL yang belum punya nilai tetap ikut tooltip, dengan nilai `null`.
  Karena itu callback `label` memeriksa `null` dulu dan menulis "Capaian: belum ada nilai".
- **Label sumbu.** Chart.js hanya meneruskan klik di area grafik, jadi kode CPL di sumbu tidak bisa
  diklik. Untuk itulah ada tombol kode CPL di bawah grafik.

## 5. Hubungan dengan proposal dan spesifikasi

- **Rumus 3 dan 4 (bagian 5.2):** capaian CPMK dan bobot BCPMK di Rincian, serta capaian CPL yang
  merupakan rata-rata tertimbangnya.
- **Bagian 5.3:** status Lulus memakai AM dari `.env` lewat `CapaianService::lulus()`. Jika AM kosong,
  tampil "Ambang belum ditetapkan".
- **Bagian 5.4:** nilai kosong diabaikan, kelas terbaru yang bernilai dipakai untuk MK yang diulang,
  dan CPL tiap kurikulum terpisah.
- **Bagian 7.2:** hijau Lulus, merah Belum Lulus, biru jika ambang belum ditetapkan, dan label angka di
  setiap batang.
- **Bagian 7.4:** kartu statistik 2 kolom di mobile dan 3 di desktop; grafik dan rincian berdampingan
  di desktop lebar dan ditumpuk di layar kecil; tinggi grafik 240/320 px.
- **ERD:** tidak ada tabel atau kolom baru.

## 6. Konsep yang muncul

- **Komponen Alpine dengan state bersama.** Satu `x-data` membungkus beberapa elemen, sehingga semuanya
  membaca `terpilih` yang sama. `x-show`, `x-bind:class`, dan `x-bind:aria-pressed` ikut berubah
  saat nilainya berubah.
- **Opsi scriptable Chart.js.** Opsi berupa fungsi yang dipanggil setiap kali grafik digambar, mis.
  untuk garis tepi batang terpilih.
- **Plugin Chart.js.** Objek dengan hook `afterDatasetsDraw` untuk menggambar teks sendiri di kanvas.
  Dipakai untuk label angka di ujung batang (`pluginLabelAngka()`).
- **Bentuk penekanan (emphasis).** Satu garis diberi warna kuat dan garis lain abu, supaya grafik dengan
  banyak garis tetap terbaca.
- **`viewData()` di test.** Test memeriksa masukan grafik (`daftarCpl`, `perkembangan`) lewat data
  view, karena grafik digambar JavaScript. `$dataGrafik` adalah variabel lokal Blade, jadi status warna
  dan CPL terpilih awal diperiksa dari isi atribut `x-data` di HTML.

## 7. Cara mencoba dan menjalankan test

Setelah `php artisan migrate:fresh --seed` dan `npm run build`:

1. **Mahasiswa Demo** (NPM `9992300001` / `mahasiswa123`). Dashboard terbuka setelah login, dengan
   Kurikulum OBE 2026, rata-rata CPL 90,63, dan 2 dari 5 CPL Lulus.
   - Klik kolom CPL03 di grafik atau tombol **CPL03**; Rincian berganti ke CPL03. Arahkan kursor ke
     kolom CPL01 untuk melihat tooltip CPL yang belum punya nilai.
   - Pilih Kurikulum 2021 di kanan atas.
2. **Hendra Kurniawan** (NPM `9992400001` / `mahasiswa123`). Kelasnya ada di dua periode, jadi grafik
   Perkembangan CPL per Periode tampil. Klik tombol CPL lain dan perhatikan garis birunya berpindah.
3. **Menu.** Klik **Capaian Pembelajaran** di sidebar, atau ikon mata di tabel Mata Kuliah yang
   Diikuti.
4. **Data simulasi dua tahun** (opsional, catatan 12). Setelah `php artisan db:seed
   --class=SimulasiSeeder`, grafik perkembangan Hendra dan Mahasiswa Demo (Kurikulum 2021) punya lima
   titik, dari 2024/2025 Ganjil sampai 2026/2027 Ganjil.

```bash
php artisan test --filter=DashboardMahasiswaTest
php artisan test --filter=SkenarioBlackBoxTest     # skenario 1, 15, dan 16 ikut berubah
npm run cek:tampilan                               # aplikasi harus berjalan
```

## 8. Ringkasan revisi

- **Tanggal:** 5 November 2026.
- **Hasil test:**
  - 305 test lulus (16 di antaranya baru, di `DashboardMahasiswaTest`), 2.037 assertion, dan Pint bersih;
  - `SkenarioBlackBoxTest` lulus dengan 224 assertion.
- **Uji mutasi:** tujuh kesalahan dimasukkan satu per satu ke `DashboardMahasiswa`, dan setiap kesalahan
  membuat test gagal. Kesalahannya: tanpa filter kurikulum, tanpa urutan periode, rincian tanpa aturan
  kelas ulang, perkembangan tanpa aturan kelas ulang, jumlah MK tanpa `unique()`, jumlah CPL Lulus yang
  salah, dan data semua mahasiswa ikut dimuat (tertangkap skenario 15).
- **Tampilan:**
  - `npm run cek:tampilan` lolos untuk 159 tangkapan layar (53 halaman × 3 lebar);
  - di Chrome, hover dan klik setiap kolom grafik serta tombol CPL berjalan tanpa error JavaScript.
- **Kendala dan perbaikannya:**
  - **Geser horizontal di 360 px.** Grid pembungkus grafik belum punya `grid-cols-1`, jadi kolomnya
    melebar mengikuti tabel perkembangan (411 px). Setelah `grid-cols-1` ditambahkan, tabel digeser di
    dalam kartunya sendiri.
  - **Tombol terpotong.** Kolom Aksi di tabel Rincian terpotong di kartu yang sempit. Kolom itu
    dihapus; tautan ke halaman Capaian Pembelajaran tetap ada di tabel Mata Kuliah yang Diikuti.
  - **Tooltip error di CPL tanpa nilai.** Review menemukan bahwa hover di kolom CPL yang belum punya
    nilai memanggil `formatAngka(null)`. Akibatnya muncul error JavaScript, dan tooltip menampilkan
    angka serta status CPL sebelumnya. Callback-nya kini memeriksa `null`, dan batang kosong memakai
    warna abu (`neutral`).
  - **Label ikut animasi.** Versi awal `pluginLabelAngka()` menghitung posisi akhir label, sehingga
    selama animasi label sudah di atas sebelum batangnya tumbuh. Posisi kini diambil dari batang,
    seperti grafik laporan sebelumnya.
  - **Test yang rapuh.** Beberapa assertion awal lulus secara kebetulan. Contohnya, angka `2` cocok
    dengan kelas CSS `text-2xl`. Assertion itu diganti dengan potongan HTML yang spesifik dan
    pemeriksaan `viewData()`.
- **Perlu diputuskan:**
  - **Spesifikasi.** Bagian 7.5 dan 7.6 spesifikasi belum menyebut Dashboard Mahasiswa. Perbarui jika
    perlu; sampai saat itu acuannya KD-74.
  - **Isi dashboard.** Isinya menurut KD-75 dan KD-76: kartu ringkasan, grafik CPL, rincian CPMK,
    perkembangan per periode, dan daftar MK. Sampaikan jika ada bagian yang ingin ditambah atau dibuang.

## 9. Pertanyaan yang mungkin diajukan penguji

1. **Apakah dashboard memakai rumus baru?**
   Tidak. Semua angka berasal dari `CapaianService`. `DashboardMahasiswa` hanya memilih kelas
   (kurikulum dan periode) lalu menyusun hasilnya untuk tampilan.
2. **Bagaimana memastikan angka di dashboard sama dengan halaman Capaian Pembelajaran?**
   Keduanya memakai `pilihKelasPerMataKuliah()` dan `hitungCapaianCpl()` yang sama. Skenario uji 15
   memeriksa rata-rata CPL Mahasiswa Demo bernilai 90,63 di kedua halaman.
3. **Dari mana angka CPL01 66,67 di test?**
   CPMK011 di INF101 bernilai 80 dengan bobot 50, dan CPMK012 di INF103 bernilai 60 dengan bobot 100.
   CPL01 = (80 × 50 + 60 × 100) / 150 = 66,67 (rumus 4). Rincian CPL01 menampilkan kedua baris itu.
4. **Mengapa grafik perkembangan Mahasiswa Demo tidak tampil?**
   Di setiap kurikulum, kelas Mahasiswa Demo baru ada di satu periode. Satu titik belum menunjukkan
   perkembangan, jadi yang tampil adalah keterangan. Akun Hendra Kurniawan punya kelas di dua periode.
   Dengan data simulasi dua tahun (catatan 12), Mahasiswa Demo punya lima periode di Kurikulum 2021.
5. **Bisakah mahasiswa melihat dashboard mahasiswa lain?**
   Tidak. Alamat dashboard tidak memuat id mahasiswa, dan datanya diambil dari akun yang login. Id
   kurikulum di alamat juga hanya diterima jika mahasiswa itu mengikuti MK dari kurikulum tersebut.
