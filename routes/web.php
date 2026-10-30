<?php

use App\Http\Controllers\Admin\AkunController;
use App\Http\Controllers\Admin\AkunPasswordController;
use App\Http\Controllers\Admin\BahanKajianController;
use App\Http\Controllers\Admin\BerandaAdminController;
use App\Http\Controllers\Admin\CplController;
use App\Http\Controllers\Admin\CpmkController;
use App\Http\Controllers\Admin\DosenController;
use App\Http\Controllers\Admin\EvaluasiKurikulumController;
use App\Http\Controllers\Admin\KurikulumController;
use App\Http\Controllers\Admin\MahasiswaController;
use App\Http\Controllers\Admin\MataKuliahController;
use App\Http\Controllers\Admin\MetodePenilaianController;
use App\Http\Controllers\Admin\PemetaanController;
use App\Http\Controllers\Admin\ProfesiController;
use App\Http\Controllers\Admin\ProfilLulusanController;
use App\Http\Controllers\Admin\ProgramStudiController;
use App\Http\Controllers\Admin\SubCpmkController;
use App\Http\Controllers\Admin\SubCpmkPenilaianController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\Koprodi\DataKurikulumController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PeranAktifController;
use App\Http\Controllers\ProfilController;
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
        Route::get('/profil', [ProfilController::class, 'show'])->name('profil');
        Route::get('/ubah-password', [PasswordController::class, 'edit'])->name('password.edit');
        Route::put('/ubah-password', [PasswordController::class, 'update'])->name('password.update');
    });

    // Halaman utama peran lain. Untuk sementara berupa halaman sambutan dan
    // diganti di iterasi 5 (Dosen), 8 (Koprodi), dan 9 (Mahasiswa).
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

// Halaman Admin (bagian 3 dan 7.5 spesifikasi).
Route::middleware(['auth', 'peran:'.Role::ADMIN])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', BerandaAdminController::class)->name('beranda');

    // Data master. Nama parameter ditulis sendiri karena Laravel menebak bentuk tunggal
    // dari kata bahasa Inggris (mis. "profesi" bisa berubah menjadi "profesus").
    Route::resource('program-studi', ProgramStudiController::class)->except('show')->parameters(['program-studi' => 'programStudi']);
    Route::resource('kurikulum', KurikulumController::class)->except('show')->parameters(['kurikulum' => 'kurikulum']);
    Route::resource('evaluasi-kurikulum', EvaluasiKurikulumController::class)->except('show')->parameters(['evaluasi-kurikulum' => 'evaluasiKurikulum']);
    Route::resource('profil-lulusan', ProfilLulusanController::class)->except('show')->parameters(['profil-lulusan' => 'profilLulusan']);
    Route::resource('profesi', ProfesiController::class)->except('show')->parameters(['profesi' => 'profesi']);
    Route::resource('cpl', CplController::class)->except('show')->parameters(['cpl' => 'cpl']);
    Route::resource('bahan-kajian', BahanKajianController::class)->except('show')->parameters(['bahan-kajian' => 'bahanKajian']);
    Route::resource('mata-kuliah', MataKuliahController::class)->parameters(['mata-kuliah' => 'mataKuliah']);
    Route::resource('cpmk', CpmkController::class)->except('show')->parameters(['cpmk' => 'cpmk']);
    Route::resource('sub-cpmk', SubCpmkController::class)->parameters(['sub-cpmk' => 'subCpmk']);

    // Rincian penilaian Sub-CPMK, dikelola di halaman detail Sub-CPMK.
    Route::post('sub-cpmk/{subCpmk}/penilaian', [SubCpmkPenilaianController::class, 'store'])->name('sub-cpmk.penilaian.store');
    Route::get('sub-cpmk/{subCpmk}/penilaian/{penilaian}/edit', [SubCpmkPenilaianController::class, 'edit'])->name('sub-cpmk.penilaian.edit');
    Route::put('sub-cpmk/{subCpmk}/penilaian/{penilaian}', [SubCpmkPenilaianController::class, 'update'])->name('sub-cpmk.penilaian.update');
    Route::delete('sub-cpmk/{subCpmk}/penilaian/{penilaian}', [SubCpmkPenilaianController::class, 'destroy'])->name('sub-cpmk.penilaian.destroy');
    Route::resource('metode-penilaian', MetodePenilaianController::class)->except('show')->parameters(['metode-penilaian' => 'metodePenilaian']);
    Route::resource('dosen', DosenController::class)->except('show')->parameters(['dosen' => 'dosen']);
    Route::resource('mahasiswa', MahasiswaController::class)->except('show')->parameters(['mahasiswa' => 'mahasiswa']);

    // Pemetaan kurikulum dalam bentuk matriks (bagian 7.6.5).
    Route::get('pemetaan', [PemetaanController::class, 'edit'])->name('pemetaan.edit');
    Route::put('pemetaan', [PemetaanController::class, 'update'])->name('pemetaan.update');

    // Akun pengguna: daftar, tambah/ubah, atur peran, dan reset password (bagian 7.6.4).
    Route::resource('akun', AkunController::class)->except('show')->parameters(['akun' => 'user']);
    Route::put('akun/{user}/password', [AkunPasswordController::class, 'update'])->name('akun.password.update');
});

// Halaman Koprodi: read-only seluruh prodi (bagian 3 spesifikasi).
Route::middleware(['auth', 'peran:'.Role::KOPRODI])->prefix('koprodi')->name('koprodi.')->group(function () {
    Route::get('kurikulum', DataKurikulumController::class)->name('kurikulum');
});
