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
*/

return [

    'ADMIN' => [
        ['label' => 'Beranda', 'route' => 'admin.beranda', 'ikon' => 'heroicon-o-home'],
    ],

    'KOPRODI' => [
        ['label' => 'Dashboard', 'route' => 'koprodi.dashboard', 'ikon' => 'heroicon-o-chart-pie'],
    ],

    'DOSEN' => [
        ['label' => 'Dashboard', 'route' => 'dosen.dashboard', 'ikon' => 'heroicon-o-squares-2x2'],
    ],

    'MAHASISWA' => [
        ['label' => 'Capaian Pembelajaran', 'route' => 'mahasiswa.capaian', 'ikon' => 'heroicon-o-academic-cap'],
    ],

];
