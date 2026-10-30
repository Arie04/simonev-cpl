<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfesiRequest;
use App\Models\Kurikulum;
use App\Models\Profesi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfesiController extends Controller
{
    public function index(Request $request): View
    {
        $kurikulum = $this->kurikulumTerpilih($request);

        return view('admin.profesi.index', [
            'kurikulum' => $kurikulum,
            'daftarKurikulum' => Kurikulum::pilihan(),
            'daftarProfesi' => Profesi::with('profilLulusan')
                ->where('kurikulum_id', $kurikulum?->kurikulum_id)
                ->cari($this->kataPencarian($request))
                ->orderBy('nama_profesi')
                ->paginate($this->jumlahPerHalaman($request))
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $kurikulum = $this->kurikulumTerpilih($request);

        if ($kurikulum === null) {
            return redirect()->route('admin.kurikulum.create')->with('gagal', 'Tambahkan kurikulum terlebih dahulu.');
        }

        // Data baru otomatis masuk ke kurikulum yang sedang dipilih (bagian 7.6.4 spesifikasi).
        return view('admin.profesi.form', [
            'profesi' => new Profesi(['kurikulum_id' => $kurikulum->kurikulum_id]),
            'kurikulum' => $kurikulum,
        ]);
    }

    public function store(ProfesiRequest $request): RedirectResponse
    {
        Profesi::create($request->validated());

        return redirect()->route('admin.profesi.index')->with('sukses', 'Profesi berhasil ditambahkan.');
    }

    public function edit(Profesi $profesi): View
    {
        return view('admin.profesi.form', [
            'profesi' => $profesi,
            'kurikulum' => $profesi->kurikulum,
        ]);
    }

    public function update(ProfesiRequest $request, Profesi $profesi): RedirectResponse
    {
        $profesi->update($request->validated());

        return redirect()->route('admin.profesi.index')->with('sukses', 'Profesi berhasil diperbarui.');
    }

    public function destroy(Profesi $profesi): RedirectResponse
    {
        // Hubungannya ke profil lulusan ikut terhapus (cascade, KD-20).
        $profesi->delete();

        return redirect()->route('admin.profesi.index')->with('sukses', 'Profesi berhasil dihapus.');
    }
}
