<?php

namespace App\Http\Controllers;

use App\Models\Kurikulum;
use App\Models\PeriodeAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Controller
{
    /**
     * Kurikulum yang dipilih di dropdown Kurikulum (bagian 7.6.4 spesifikasi).
     *
     * Pilihan disimpan di session agar tetap sama saat berpindah halaman data master.
     * Jika belum pernah memilih, dipakai kurikulum terbaru (tahun_berlaku terbesar).
     */
    protected function kurikulumTerpilih(Request $request): ?Kurikulum
    {
        $kurikulumId = $request->query('kurikulum', $request->session()->get('kurikulum_terpilih'));

        $kurikulum = is_string($kurikulumId) && Str::isUuid($kurikulumId)
            ? Kurikulum::find($kurikulumId)
            : null;

        $kurikulum ??= Kurikulum::terbaru();

        if ($kurikulum !== null) {
            $request->session()->put('kurikulum_terpilih', $kurikulum->kurikulum_id);
        }

        return $kurikulum;
    }

    /**
     * Periode yang dipilih di dropdown Periode Akademik. Pilihan disimpan di session seperti
     * kurikulum. Bawaannya periode aktif (KD-45), atau periode terbaru jika belum ada kelas.
     */
    protected function periodeTerpilih(Request $request): ?PeriodeAkademik
    {
        $periodeId = $request->query('periode', $request->session()->get('periode_terpilih'));

        $periode = is_string($periodeId) && Str::isUuid($periodeId)
            ? PeriodeAkademik::find($periodeId)
            : null;

        $periode ??= PeriodeAkademik::aktif() ?? PeriodeAkademik::urutTerbaru()->first();

        if ($periode !== null) {
            $request->session()->put('periode_terpilih', $periode->periode_akademik_id);
        }

        return $periode;
    }

    /**
     * Kata kunci dari kotak "Cari data..."; selain teks biasa dianggap kosong.
     */
    protected function kataPencarian(Request $request): ?string
    {
        $kataKunci = $request->query('cari');

        return is_string($kataKunci) ? $kataKunci : null;
    }

    /**
     * Jumlah baris per halaman tabel: 10 (bawaan), 25, atau 50.
     */
    protected function jumlahPerHalaman(Request $request): int
    {
        $jumlah = $request->integer('per_halaman', 10);

        return in_array($jumlah, [10, 25, 50], true) ? $jumlah : 10;
    }
}
