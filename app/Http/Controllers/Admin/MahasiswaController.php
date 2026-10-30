<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MahasiswaRequest;
use App\Models\Mahasiswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MahasiswaController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.mahasiswa.index', [
            'daftarMahasiswa' => Mahasiswa::cari($this->kataPencarian($request))
                ->orderByDesc('angkatan')
                ->orderBy('nim')
                ->paginate($this->jumlahPerHalaman($request))
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.mahasiswa.form', ['mahasiswa' => new Mahasiswa]);
    }

    public function store(MahasiswaRequest $request): RedirectResponse
    {
        // Sekaligus membuat akun login dengan peran MAHASISWA (bagian 7.6.4 spesifikasi).
        Mahasiswa::buatBesertaAkun($request->validated());

        return redirect()->route('admin.mahasiswa.index')->with('sukses', 'Data mahasiswa dan akunnya berhasil ditambahkan.');
    }

    public function edit(Mahasiswa $mahasiswa): View
    {
        return view('admin.mahasiswa.form', ['mahasiswa' => $mahasiswa]);
    }

    public function update(MahasiswaRequest $request, Mahasiswa $mahasiswa): RedirectResponse
    {
        $mahasiswa->ubahBesertaAkun($request->validated());

        return redirect()->route('admin.mahasiswa.index')->with('sukses', 'Data mahasiswa berhasil diperbarui.');
    }

    public function destroy(Mahasiswa $mahasiswa): RedirectResponse
    {
        if ($pesan = $mahasiswa->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        $mahasiswa->hapusBesertaAkun();

        return redirect()->route('admin.mahasiswa.index')->with('sukses', 'Data mahasiswa berhasil dihapus.');
    }
}
