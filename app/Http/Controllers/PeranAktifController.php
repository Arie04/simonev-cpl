<?php

namespace App\Http\Controllers;

use App\Http\Requests\PilihPeranRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PeranAktifController extends Controller
{
    /**
     * Langkah pilih peran setelah login, sekaligus halaman "Ganti Peran" (KD-26).
     */
    public function create(Request $request): View|RedirectResponse
    {
        $daftarPeran = $request->user()->roles()->orderBy('nama_role')->get();

        // Peran dicabut Admin setelah pengguna login.
        if ($daftarPeran->isEmpty()) {
            return $this->keluarKarenaTanpaPeran($request);
        }

        if ($daftarPeran->count() === 1) {
            $request->session()->put('peran_aktif', $daftarPeran->first()->kode_role);

            return redirect()->route('beranda');
        }

        return view('peran-aktif.create', [
            'daftarPeran' => $daftarPeran,
            'kodePeranAktif' => $request->session()->get('peran_aktif'),
        ]);
    }

    public function store(PilihPeranRequest $request): RedirectResponse
    {
        $role = Role::find($request->validated('role_id'));

        $request->session()->put('peran_aktif', $role->kode_role);

        return redirect()->route('beranda')->with('sukses', "Anda masuk sebagai {$role->nama_role}.");
    }

    private function keluarKarenaTanpaPeran(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['identitas' => __('auth.tanpa_peran')]);
    }
}
