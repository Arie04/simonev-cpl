<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AkunRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Menu Akun Pengguna: daftar akun, tambah/ubah, atur peran, dan hapus (KD-36).
 * Akun dosen dan mahasiswa dibuat lewat Data Master Dosen/Mahasiswa.
 */
class AkunController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.akun.index', [
            'daftarAkun' => User::with(['roles', 'dosen', 'mahasiswa'])
                ->cari($this->kataPencarian($request))
                ->orderBy('nama')
                ->paginate($this->jumlahPerHalaman($request))
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new User);
    }

    public function store(AkunRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::create($request->safe()->only(['nama', 'email', 'password']));
            $user->simpanPeran($request->validated('peran'));
        });

        return redirect()->route('admin.akun.index')->with('sukses', 'Akun berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return $this->form($user);
    }

    public function update(AkunRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $user->update($request->safe()->only(['nama', 'email']));
            // Nama disimpan sama di users dan dosen/mahasiswa (bagian 7.6.4 spesifikasi).
            $user->dosen?->update(['nama' => $user->nama]);
            $user->mahasiswa?->update(['nama' => $user->nama]);
            $user->simpanPeran($request->validated('peran'));
        });

        return redirect()->route('admin.akun.index')->with('sukses', 'Akun berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('gagal', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        if ($pesan = $user->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        $user->delete();

        return redirect()->route('admin.akun.index')->with('sukses', 'Akun berhasil dihapus.');
    }

    private function form(User $user): View
    {
        $peranYangBoleh = $user->peranYangBoleh();

        return view('admin.akun.form', [
            'user' => $user,
            // Peran yang bisa dicentang beserta namanya, mis. ['ADMIN' => 'Admin', ...].
            'pilihanPeran' => array_intersect_key(Role::DAFTAR_PERAN, array_flip($peranYangBoleh)),
            'peranTerpilih' => $user->exists ? $user->roles->pluck('kode_role')->all() : [],
        ]);
    }
}
