<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class CekPeran
{
    /**
     * Memastikan pengguna sudah memilih peran aktif dan peran itu boleh membuka
     * route ini (KD-26). Contoh: ->middleware('peran:ADMIN'). Tanpa parameter,
     * cukup dipastikan pengguna sudah memilih peran aktif.
     */
    public function handle(Request $request, Closure $next, string ...$peranDiizinkan): Response
    {
        $peranAktif = $request->user()->roles->firstWhere('kode_role', $request->session()->get('peran_aktif'));

        // Peran belum dipilih, atau sudah dicabut Admin setelah dipilih.
        if ($peranAktif === null) {
            $request->session()->forget('peran_aktif');

            return redirect()->route('peran-aktif.create');
        }

        // Akses dicek terhadap peran aktif, bukan semua peran milik pengguna.
        if ($peranDiizinkan !== [] && ! in_array($peranAktif->kode_role, $peranDiizinkan, true)) {
            abort(403);
        }

        // Top bar dan sidebar membutuhkan nama dan kode peran aktif.
        View::share('peranAktif', $peranAktif);

        return $next($request);
    }
}
