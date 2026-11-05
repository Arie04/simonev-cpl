# 13 · Menu akun tanpa Ubah Password (revisi)

Catatan revisi kecil setelah [12-simulasi-dua-tahun.md](12-simulasi-dua-tahun.md). Keputusannya dicatat
sebagai KD-81 di [keputusan-desain.md](../keputusan-desain.md).

## 1. Tujuan

Menu akun di top bar sebelumnya berisi Profil, Ubah Password, Ganti Peran (hanya untuk pengguna
yang punya lebih dari satu peran), dan Keluar. Halaman Profil sudah punya tombol **Ubah Password**
sejak iterasi 1, jadi butir itu muncul dua kali. Atas permintaan pemilik proyek, butir Ubah Password
dihapus dari menu akun. Halaman Ubah Password tetap ada dan dibuka dari halaman Profil.

## 2. File yang diubah

| File | Perubahan |
|---|---|
| `resources/views/components/menu-akun.blade.php` | Tautan Ubah Password dihapus, diganti komentar yang menunjuk KD-81 |
| `resources/views/admin/dosen/form.blade.php`, `resources/views/admin/mahasiswa/form.blade.php` | Keterangan Password Awal kini berbunyi "…bisa menggantinya lewat halaman Profil." |
| `tests/Feature/Akun/ProfilTest.php` | Test baru `test_ubah_password_dibuka_dari_profil_bukan_dari_menu_akun` |
| `docs/keputusan-desain.md`, `CHANGELOG.md`, `docs/pengujian/black-box.md`, catatan 01 | KD-81, catatan perubahan, jumlah test, dan langkah mencoba |

Route, `PasswordController`, `UbahPasswordRequest`, dan halaman `password/edit.blade.php` tidak berubah.

## 3. Penjelasan perubahan

Menu akun adalah komponen Blade `menu-akun` yang dipanggil oleh `top-bar`. Karena itu, satu
perubahan berlaku di semua halaman dan untuk semua peran. Isi menunya sekarang (kelas CSS disingkat):

```blade
{{-- Ubah Password tidak ada di menu ini; tombolnya ada di halaman Profil (KD-81). --}}
<a href="{{ route('profil') }}" …>Profil</a>
@if ($user->roles->count() > 1)
    <a href="{{ route('peran-aktif.create') }}" …>Ganti Peran</a>
@endif
<form method="POST" action="{{ route('logout') }}" …>… Keluar</form>
```

Tombol di halaman Profil (`resources/views/profil/show.blade.php`) tidak diubah:

```blade
<x-tombol varian="outline" :href="route('password.edit')">
    <x-heroicon-o-key class="size-5" />
    Ubah Password
</x-tombol>
```

Test baru membuka halaman utama keempat peran: Beranda Admin, Dashboard Koprodi, Dashboard Dosen,
dan Dashboard Mahasiswa. Di setiap halaman itu, tautan Profil harus ada, sedangkan tautan
`/ubah-password` dan teks "Ubah Password" tidak boleh ada. Setelah itu, halaman Profil peran yang
sama harus memuat tautan `/ubah-password`.

## 4. Hubungan dengan spesifikasi

Bagian 7.1 (top bar), 7.5 (menu per peran), dan 7.6 butir 13 spesifikasi masih menyebut Ubah Password
di menu akun. Spesifikasi tidak diubah; acuannya KD-81, sama seperti revisi Dashboard Mahasiswa
(KD-74).

## 5. Cara mencoba dan menjalankan test

1. Login dengan akun mana pun, mis. Mahasiswa Demo (NPM `9992300001` / `mahasiswa123`).
2. Klik avatar di kanan atas. Menu akun berisi Profil dan Keluar. Ganti Peran hanya muncul untuk
   akun yang punya dua peran, mis. `koprodi.dosen@simonev.test` / `koprodi.dosen123`.
3. Klik **Profil**, lalu klik **Ubah Password**.

```bash
php artisan test --filter=ProfilTest
```

## 6. Ringkasan revisi

- **Tanggal:** 5 November 2026.
- **Hasil test:** 317 test lulus (1 di antaranya baru, di `ProfilTest`), 5.092 assertion, dan Pint
  bersih.
- **Uji mutasi:** dua kesalahan dimasukkan satu per satu, dan test baru gagal pada keduanya:
  - tautan Ubah Password dikembalikan ke menu akun;
  - tombol Ubah Password dihapus dari halaman Profil.
- **Tampilan:** menu akun dibuka di Chrome pada lebar 1280 px dan 360 px, dengan akun Mahasiswa Demo
  dan akun Koprodi + Dosen. Isinya Profil dan Keluar, ditambah Ganti Peran untuk akun berperan dua.
  Menu tetap di dalam layar 360 px, dan tidak ada error JavaScript.

## 7. Pertanyaan yang mungkin diajukan penguji

1. **Mengapa Ubah Password tidak ada di menu akun, padahal spesifikasi menyebutnya?**
   Tombol yang sama sudah ada di halaman Profil, jadi menu akun cukup memuat Profil. Perubahan ini
   adalah revisi dari pemilik proyek dan dicatat sebagai KD-81.
2. **Apakah halaman Ubah Password masih bisa dibuka langsung lewat alamatnya?**
   Bisa. Route `/ubah-password` tidak berubah dan tetap berada di grup route yang butuh login dan
   peran aktif. Tamu yang membukanya diarahkan ke halaman login (`HakAksesTest`).
3. **Bagaimana memastikan pengguna tidak kehilangan jalan ke halaman Ubah Password?**
   Test `test_ubah_password_dibuka_dari_profil_bukan_dari_menu_akun` memeriksa keempat peran. Menu
   akun tidak boleh memuat tautan Ubah Password, dan halaman Profil harus memuatnya. Uji mutasi
   membuktikan test itu gagal jika salah satu syarat dilanggar.
