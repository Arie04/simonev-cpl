# 08 · Dashboard Koprodi dan Laporan Capaian

Catatan iterasi 8 (bagian 11 spesifikasi). Dibaca setelah [07-perhitungan-capaian.md](07-perhitungan-capaian.md).

## 1. Tujuan dan kebutuhan yang dipenuhi

Menampilkan hasil `CapaianService` kepada Koprodi dan Dosen:

- **Dashboard Monitoring Koprodi:** kartu statistik dan Ringkasan Capaian CPL (donut + tabel).
- **Laporan Capaian Pembelajaran:**
  - sembilan filter;
  - tab CPL | CPMK | Sub-CPMK;
  - kartu ringkasan, tabel, dan grafik batang.
- **Cakupan berbeda per peran:** Koprodi melihat seluruh prodi, sedangkan Dosen hanya kelas yang ia
  ampu.

Kebutuhan yang dipenuhi:

- **KF-12:** dashboard monitoring.
- **KF-13:** laporan capaian prodi.
- **KF-14:** laporan dosen untuk kelas yang diampu.
- **KF-16:** filter laporan.
- **Skenario uji bagian 12:**
  - nomor 12 (dashboard tampil);
  - nomor 13 (data sesuai filter);
  - nomor 14 (laporan dosen hanya kelasnya).

## 2. File yang dibuat atau diubah

| File | Peran |
|---|---|
| `app/Services/LaporanCapaian.php` | Cakupan peserta, baris tiap tab, label, urutan, ringkasan; ringkasan CPL dashboard |
| `app/Services/FilterLaporan.php` | Pilihan dan isian kartu filter (dibatasi untuk Dosen) |
| `app/Http/Controllers/LaporanCapaianController.php` | Satu controller untuk `/koprodi/laporan` dan `/dosen/laporan` |
| `app/Http/Controllers/Koprodi/DashboardKoprodiController.php` | Dashboard Koprodi |
| `resources/views/laporan/index.blade.php`, `koprodi/dashboard.blade.php` | Halaman laporan dan dashboard |
| `resources/views/components/status-capaian.blade.php` | Badge Tercapai / Tidak Tercapai / Belum ada nilai / Ambang belum ditetapkan |
| `resources/js/app.js` | `grafikLaporan` (batang) dan `grafikDonut` dengan Chart.js |
| `resources/css/app.css` | `@theme static` agar token warna tersedia untuk grafik |
| `routes/web.php`, `config/menu.php` | Route dan menu Laporan Capaian |
| `tests/Feature/LaporanCapaianTest.php` | Test iterasi ini |

## 3. Alur kerja

```mermaid
sequenceDiagram
    actor U as Koprodi / Dosen
    participant C as LaporanCapaianController
    participant F as FilterLaporan
    participant L as LaporanCapaian
    participant S as CapaianService
    U->>C: GET /koprodi/laporan?tab=cpmk&periode=…
    C->>F: new FilterLaporan(request, dosen?)
    Note over F: pilihan hanya dari kelas yang boleh dilihat;<br/>nilai di alamat yang bukan pilihan diabaikan
    C->>L: susun(tab, filter)
    L->>L: pesertaDalamCakupan(filter)
    L->>S: capaianPerItem(peserta)
    L->>S: rekap(capaian tiap item)
    L-->>C: baris + ringkasan
    C-->>U: kartu, tabel, grafik
```

## 4. Penjelasan potongan kode penting

### a. Satu controller, dua cakupan

```php
if ($request->session()->get('peran_aktif') === Role::DOSEN) {
    $dosen = $request->user()->dosen;
    abort_if($dosen === null, 403);
}

$filter = new FilterLaporan($request, $dosen);
```

- **Cakupan dari peran aktif.** Route `/koprodi/laporan` dan `/dosen/laporan` memakai controller yang
  sama. Jika peran aktif Dosen, semua pilihan dan perhitungan dibatasi ke kelas yang ia ampu (KD-46,
  KD-67).
- **Tetap dijaga middleware.** Middleware `peran:KOPRODI` dan `peran:DOSEN` di route tetap memastikan
  hanya peran itu yang bisa membuka halamannya.

### b. Cakupan peserta

```php
PesertaKelas::with(CapaianService::RELASI_PESERTA)
    ->whereHas('kelasKuliah', fn ($query) => $query
        ->whereHas('mataKuliah', fn ($query) => $query->where('kurikulum_id', $filter['kurikulum_id']))
        ->when($filter['periode_id'] ?? null, fn ($query, $periodeId) => $query->where('periode_akademik_id', $periodeId))
        ...
        ->when($filter['dosen_id'] ?? null, fn ($query, $dosenId) => $query->whereHas('dosen', ...)))
    ->when($filter['angkatan'] ?? null, ...)
```

- **`when()`.** Kondisi query hanya ditambahkan jika filternya diisi.
- **Filter periode.** Hanya kelas pada periode itu yang ikut, jadi CPL dihitung dari periode itu saja
  (KD-23).
- **Tanpa filter periode.** Semua kelas ikut. Aturan mengulang memilih satu kelas per mahasiswa per MK.

### c. Label dan urutan baris CPMK

```php
->sortBy([['cpmk.kode_cpmk', 'asc'], ['mataKuliah.kode_mata_kuliah', 'asc']])
->map(fn ($pemetaan) => [
    'kode' => $satuMataKuliah ? $pemetaan->cpmk->kode_cpmk : $pemetaan->label(),   // "CPMK063 · MK04"
    ...
```

- **Satu baris per pasangan.** Setiap baris adalah satu pasangan CPMK–MK, karena capaian CPMK
  dihitung per pasangan (rumus 3).
- **Urutan.** Urutan kode CPMK lalu kode MK membuat CPMK yang sama letaknya berdekatan.
- **Label pendek.** Jika laporan disaring satu MK, labelnya cukup kode CPMK (KD-25).

### d. Status dan ambang di tampilan

```blade
@if ($rekap['jumlah_mahasiswa'] === 0)        <x-badge>Belum ada nilai</x-badge>
@elseif ($rekap['tercapai'] === true)         <x-badge varian="sukses" ikon="heroicon-o-check">Tercapai</x-badge>
@elseif ($rekap['tercapai'] === false)        <x-badge varian="bahaya" ikon="heroicon-o-x-mark">Tidak Tercapai</x-badge>
@else                                         <x-badge>Ambang belum ditetapkan</x-badge>
@endif
```

- **Status tidak dihitung di view.** View hanya membaca hasil `rekap()` dari `CapaianService`.
- **Teks dan ikon.** Badge selalu memuat teks dan ikon, tidak hanya warna (bagian 7.2).

### e. Grafik batang

```js
const warnaStatus = { tercapai: warna('success-chart'), tidak: warna('danger-chart'), netral: warna('primary-700') };
...
daftarDataset.push({ type: 'line', data: data.label.map(() => data.ambangKetercapaian), borderDash: [6, 4], ... });
```

- **Data dari view.** View mengirim label, nilai, dan status setiap item lewat `Js::from(...)`.
- **Warna dan garis AK.** JavaScript memilih warna dari token CSS (`--color-success-chart`, dan
  seterusnya), lalu menambahkan garis putus-putus AK sebagai dataset garis.
- **Label angka.** Plugin kecil `labelAngka` menulis angka di atas setiap batang.

## 5. Hubungan dengan proposal dan spesifikasi

- **Bagian 5.3:**
  - agregat berupa % mahasiswa Lulus, dan rata-rata sebagai informasi pendukung;
  - dashboard selalu kumulatif, mengikuti kurikulum dan angkatan;
  - keterangan cakupan periode tampil di laporan.
- **Bagian 7.6.2 (Gambar 3.8):**
  - dropdown kurikulum dan enam kartu;
  - donut dan tabel Kategori, Jumlah CPL, Persentase, dan Total;
  - label kategori menyebut ambang, dan pemberitahuan tampil jika ambang belum diisi.
- **Bagian 7.6.9 (Gambar 3.14):**
  - filter, informasi ambang, tab, dan empat kartu;
  - tabel di kiri dan grafik di kanan;
  - batang hijau/merah, atau biru jika ambang belum ditetapkan, dengan garis putus-putus AK.
- **Bagian 7.4:**
  - kartu 2 kolom di mobile, 3 di dashboard, dan 4 di laporan;
  - panel filter bisa dibuka-tutup;
  - tinggi grafik ±240/320px;
  - legenda donut di bawah;
  - batang mendatar di mobile jika item banyak.

## 6. Konsep Laravel yang muncul

- **Dependency injection di method controller:** `__invoke(Request $request, LaporanCapaian $laporan, CapaianService $capaian)`;
  Laravel membuat objek service otomatis.
- **`when()` pada query builder:** menambah kondisi hanya jika nilainya ada.
- **`Js::from()` di Blade:** mengirim data PHP ke JavaScript dengan aman.
- **Collection `sortBy([[kolom, arah], ...])`:** mengurutkan berdasarkan beberapa kolom.
- **Dua route ke satu controller:** route `koprodi.laporan` dan `dosen.laporan`, yang masing-masing
  dijaga middleware `peran` berbeda.

## 7. Cara mencoba dan menjalankan test

Setelah `php artisan migrate:fresh --seed` (ambang contoh 50.01 dan 85 di `.env`):

1. **Dashboard.** Login sebagai `koprodi@simonev.test` / `koprodi123`:
   - Kurikulum OBE 2026 menampilkan 3 CPL Tercapai, 1 Tidak Tercapai, dan 1 Belum ada nilai;
   - ganti angkatan dan kurikulum untuk melihat perubahannya.
2. **Laporan Capaian.** Buka menu Laporan Capaian:
   - coba tab CPMK; pada Kurikulum 2021, CPMK032 tampil dua kali (INF202 dan INF301);
   - pilih periode 2025/2026 Genap; keterangan berubah menjadi "CPL dihitung dari kelas pada periode
     ini saja";
   - pilih satu mata kuliah; label berubah menjadi kode CPMK saja.
3. **Laporan Dosen.** Login sebagai `dosen@simonev.test` / `dosen123`, lalu buka Laporan Capaian.
   Hanya kelas INF201, INF202, dan INF102 yang dihitung.
4. **Ambang kosong.** Kosongkan `AMBANG_KETERCAPAIAN` di `.env`, lalu jalankan
   `php artisan config:clear`. Dashboard menampilkan "Ambang ketercapaian belum ditetapkan program
   studi".

```bash
php artisan test --filter=LaporanCapaianTest
```

## 8. Ringkasan iterasi

- **Tanggal:** 30 Oktober 2026.
- **Hasil test:** 265 test lulus (12 di antaranya baru), dan Pint bersih.
- **Tampilan:** Dashboard Koprodi, laporan tab CPL/CPMK/Sub-CPMK, dan laporan Dosen sudah dicek di
  360, 768, dan 1280px tanpa geser horizontal.
- **Kendala dan perbaikannya:**
  - **Legenda donut dan tabel ringkasan terpotong.** Legenda dipindah ke bawah, dan label kategori di
    tabel dipecah menjadi badge dan keterangan kecil.
  - **Kolom status tabel laporan sempit.** Proporsi tabel:grafik diubah menjadi 3:2.
  - **Grafik mendatar di 360px.** Labelnya sempat terpotong dan sebagian dilewati. Sekarang label
    dipecah dua baris, semua label ditampilkan, dan tinggi grafik mengikuti jumlah item.
  - **Pilihan filter Dosen.** Test menemukan bahwa dropdown CPMK di laporan Dosen masih memuat CPMK
    mata kuliah dosen lain. Pilihan CPL/CPMK dan baris CPL laporan Dosen kini dibatasi ke MK yang ia
    ampu (KD-69).
- **Perlu diputuskan:** tidak ada keputusan baru dari user. KD-67 sampai KD-70 adalah keputusan
  teknis.

## 9. Pertanyaan yang mungkin diajukan penguji

1. **Apa arti donut di dashboard?**
   Jumlah CPL yang Tercapai (≥ AK mahasiswa lulus), Tidak Tercapai, dan yang belum punya nilai,
   dihitung kumulatif dari semua periode untuk kurikulum dan angkatan terpilih.
2. **Mengapa grafik laporan menampilkan % mahasiswa lulus, bukan rata-rata?**
   Ketercapaian ditentukan dari persentase mahasiswa yang lulus (bagian 5.3). Rata-rata tetap
   tampil di tabel sebagai informasi pendukung.
3. **Bagaimana laporan dosen dibatasi?**
   Semua pilihan filter dan data peserta dibatasi ke kelas yang tercatat di `dosen_kelas` untuk dosen
   itu. Id lain yang diketik di alamat diabaikan.
4. **Apa beda filter "Semua periode" dan satu periode?**
   Dengan satu periode, hanya kelas pada periode itu yang dihitung. Dengan semua periode, CPL
   kumulatif, dan mahasiswa yang mengulang dihitung sekali dari kelas terbaru yang sudah bernilai.
5. **Mengapa ada baris "CPMK063 · MK04"?**
   CPMK yang sama bisa dipakai di beberapa MK dengan bobot berbeda, dan capaiannya dihitung per
   pasangan CPMK–MK (rumus 3). Kode MK membuat labelnya tetap pendek di grafik.
