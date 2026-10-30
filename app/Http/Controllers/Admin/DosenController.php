<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DosenRequest;
use App\Models\Dosen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DosenController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.dosen.index', [
            'daftarDosen' => Dosen::with('user')
                ->cari($this->kataPencarian($request))
                ->orderBy('nama')
                ->paginate($this->jumlahPerHalaman($request))
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.dosen.form', ['dosen' => new Dosen]);
    }

    public function store(DosenRequest $request): RedirectResponse
    {
        // Sekaligus membuat akun login dengan peran DOSEN (bagian 7.6.4 spesifikasi).
        Dosen::buatBesertaAkun($request->validated());

        return redirect()->route('admin.dosen.index')->with('sukses', 'Data dosen dan akunnya berhasil ditambahkan.');
    }

    public function edit(Dosen $dosen): View
    {
        return view('admin.dosen.form', ['dosen' => $dosen]);
    }

    public function update(DosenRequest $request, Dosen $dosen): RedirectResponse
    {
        $dosen->ubahBesertaAkun($request->validated());

        return redirect()->route('admin.dosen.index')->with('sukses', 'Data dosen berhasil diperbarui.');
    }

    public function destroy(Dosen $dosen): RedirectResponse
    {
        if ($pesan = $dosen->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        $dosen->hapusBesertaAkun();

        return redirect()->route('admin.dosen.index')->with('sukses', 'Data dosen berhasil dihapus.');
    }
}
