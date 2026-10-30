<?php

namespace App\Support;

use Illuminate\Support\Number;

/**
 * Format angka untuk tampilan: koma desimal seperti buku APTIKOM, mis. "78,40" (bagian 7.3).
 */
class Angka
{
    /**
     * Dua angka di belakang koma, mis. 78.4 → "78,40". Null ditampilkan "–".
     */
    public static function format(int|float|string|null $nilai, int $desimal = 2): string
    {
        return $nilai === null ? '–' : Number::format((float) $nilai, $desimal, locale: 'id');
    }

    /**
     * Tanpa nol di belakang koma, mis. 40.00 → "40" dan 12.50 → "12,5". Dipakai untuk bobot.
     */
    public static function ringkas(int|float|string|null $nilai): string
    {
        return $nilai === null ? '' : Number::format((float) $nilai, maxPrecision: 2, locale: 'id');
    }

    /**
     * Mengubah isian dengan koma desimal menjadi titik sebelum validasi, mis. "12,5" → "12.5".
     */
    public static function normalisasi(mixed $isian): mixed
    {
        return is_string($isian) ? str_replace(',', '.', trim($isian)) : $isian;
    }

    /**
     * Dua bobot dianggap sama jika selisihnya kurang dari 0,005, karena bobot disimpan dengan
     * dua angka di belakang koma.
     */
    public static function sama(int|float|string $pertama, int|float|string $kedua): bool
    {
        return abs((float) $pertama - (float) $kedua) < 0.005;
    }
}
