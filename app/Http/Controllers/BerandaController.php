<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BerandaController extends Controller
{
    /**
     * Mengarahkan ke halaman utama sesuai peran aktif. Middleware 'peran'
     * sudah memastikan peran aktif ada dan masih dimiliki pengguna.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route(match ($request->session()->get('peran_aktif')) {
            Role::ADMIN => 'admin.beranda',
            Role::KOPRODI => 'koprodi.dashboard',
            Role::DOSEN => 'dosen.dashboard',
            Role::MAHASISWA => 'mahasiswa.capaian',
        });
    }
}
