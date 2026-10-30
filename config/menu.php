<?php

/*
|--------------------------------------------------------------------------
| Menu Sidebar per Peran (bagian 7.5 spesifikasi)
|--------------------------------------------------------------------------
|
| Kunci array adalah kode peran (lihat konstanta di App\Models\Role).
| Menu hanya berisi halaman yang sudah selesai dibuat; setiap iterasi
| menambahkan menu untuk fiturnya sendiri, jadi tidak ada tautan mati (KD-32).
|
| Setiap menu berisi:
| - 'label' dan 'ikon' (Heroicons outline);
| - 'route' untuk tautan, dan 'aktif' berupa pola nama route yang membuat
|   menu ini ditandai aktif (mis. 'admin.cpl.*' juga aktif di form CPL);
| - atau 'anak' untuk grup menu yang bisa dibuka-tutup (mis. Data Master).
|
*/

return [

    'ADMIN' => [
        ['label' => 'Beranda', 'route' => 'admin.beranda', 'aktif' => 'admin.beranda', 'ikon' => 'heroicon-o-home'],
    ],

    'KOPRODI' => [
        ['label' => 'Dashboard', 'route' => 'koprodi.dashboard', 'aktif' => 'koprodi.dashboard', 'ikon' => 'heroicon-o-chart-pie'],
    ],

    'DOSEN' => [
        ['label' => 'Dashboard', 'route' => 'dosen.dashboard', 'aktif' => 'dosen.dashboard', 'ikon' => 'heroicon-o-squares-2x2'],
    ],

    'MAHASISWA' => [
        ['label' => 'Capaian Pembelajaran', 'route' => 'mahasiswa.capaian', 'aktif' => 'mahasiswa.capaian', 'ikon' => 'heroicon-o-academic-cap'],
    ],

];
