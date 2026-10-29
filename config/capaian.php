<?php

/*
|--------------------------------------------------------------------------
| Ambang Batas Capaian (bagian 5.3 spesifikasi)
|--------------------------------------------------------------------------
|
| Nilai ambang ditetapkan program studi dan diisi pengelola server di file
| .env. Kode aplikasi membacanya lewat config('capaian.…'), bukan env(),
| karena env() bernilai null ketika config sudah di-cache. Jika di .env
| kosong, nilainya null dan status ditampilkan "Ambang belum ditetapkan".
|
*/

return [

    // Ambang Batas Kelulusan Mahasiswa (AM), skala 0–100.
    'ambang_kelulusan_mahasiswa' => env('AMBANG_KELULUSAN_MAHASISWA'),

    // Ambang Batas Ketercapaian (AK), persen mahasiswa lulus 0–100.
    'ambang_ketercapaian' => env('AMBANG_KETERCAPAIAN'),

];
