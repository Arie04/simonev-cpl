<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\PeranAktifController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Pilih peran aktif setelah login, juga dipakai untuk "Ganti Peran".
    Route::get('/pilih-peran', [PeranAktifController::class, 'create'])->name('peran-aktif.create');
    Route::post('/pilih-peran', [PeranAktifController::class, 'store'])->name('peran-aktif.store');

    // Halaman untuk semua peran: cukup sudah memilih peran aktif.
    Route::middleware('peran')->group(function () {
        Route::get('/', BerandaController::class)->name('beranda');
    });

    // Halaman utama tiap peran. Untuk sementara berupa halaman sambutan dan
    // diganti di iterasi 2 (Admin), 5 (Dosen), 8 (Koprodi), dan 9 (Mahasiswa).
    Route::view('/admin', 'beranda.sementara', ['judul' => 'Beranda', 'iterasi' => 2])
        ->middleware('peran:'.Role::ADMIN)
        ->name('admin.beranda');
    Route::view('/koprodi', 'beranda.sementara', ['judul' => 'Dashboard', 'iterasi' => 8])
        ->middleware('peran:'.Role::KOPRODI)
        ->name('koprodi.dashboard');
    Route::view('/dosen', 'beranda.sementara', ['judul' => 'Dashboard', 'iterasi' => 5])
        ->middleware('peran:'.Role::DOSEN)
        ->name('dosen.dashboard');
    Route::view('/mahasiswa', 'beranda.sementara', ['judul' => 'Capaian Pembelajaran', 'iterasi' => 9])
        ->middleware('peran:'.Role::MAHASISWA)
        ->name('mahasiswa.capaian');
});
