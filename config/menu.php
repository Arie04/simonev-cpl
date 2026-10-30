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
        [
            'label' => 'Data Master',
            'ikon' => 'heroicon-o-circle-stack',
            'anak' => [
                ['label' => 'Program Studi', 'route' => 'admin.program-studi.index', 'aktif' => 'admin.program-studi.*'],
                ['label' => 'Kurikulum', 'route' => 'admin.kurikulum.index', 'aktif' => 'admin.kurikulum.*'],
                ['label' => 'Evaluasi Kurikulum', 'route' => 'admin.evaluasi-kurikulum.index', 'aktif' => 'admin.evaluasi-kurikulum.*'],
                ['label' => 'Profil Lulusan', 'route' => 'admin.profil-lulusan.index', 'aktif' => 'admin.profil-lulusan.*'],
                ['label' => 'Profesi', 'route' => 'admin.profesi.index', 'aktif' => 'admin.profesi.*'],
                ['label' => 'CPL', 'route' => 'admin.cpl.index', 'aktif' => 'admin.cpl.*'],
                ['label' => 'Bahan Kajian', 'route' => 'admin.bahan-kajian.index', 'aktif' => 'admin.bahan-kajian.*'],
                ['label' => 'Mata Kuliah', 'route' => 'admin.mata-kuliah.index', 'aktif' => 'admin.mata-kuliah.*'],
                ['label' => 'CPMK', 'route' => 'admin.cpmk.index', 'aktif' => 'admin.cpmk.*'],
                ['label' => 'Metode Penilaian', 'route' => 'admin.metode-penilaian.index', 'aktif' => 'admin.metode-penilaian.*'],
                ['label' => 'Dosen', 'route' => 'admin.dosen.index', 'aktif' => 'admin.dosen.*'],
                ['label' => 'Mahasiswa', 'route' => 'admin.mahasiswa.index', 'aktif' => 'admin.mahasiswa.*'],
            ],
        ],
        ['label' => 'Akun Pengguna', 'route' => 'admin.akun.index', 'aktif' => 'admin.akun.*', 'ikon' => 'heroicon-o-users'],
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
