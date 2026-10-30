<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MataKuliahRequest;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MataKuliahController extends Controller
{
    public function index(Request $request): View
    {
        $kurikulum = $this->kurikulumTerpilih($request);

        return view('admin.mata-kuliah.index', [
            'kurikulum' => $kurikulum,
            'daftarKurikulum' => Kurikulum::pilihan(),
            'daftarMataKuliah' => MataKuliah::where('kurikulum_id', $kurikulum?->kurikulum_id)
                ->cari($this->kataPencarian($request))
                ->orderBy('semester')->orderBy('kode_mata_kuliah')
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
        return view('admin.mata-kuliah.form', [
            'mataKuliah' => new MataKuliah(['kurikulum_id' => $kurikulum->kurikulum_id]),
            'kurikulum' => $kurikulum,
        ]);
    }

    public function store(MataKuliahRequest $request): RedirectResponse
    {
        MataKuliah::create($request->validated());

        return redirect()->route('admin.mata-kuliah.index')->with('sukses', 'Mata kuliah berhasil ditambahkan.');
    }

    /**
     * Detail mata kuliah beserta ringkasan bobot CPMK dan Sub-CPMK, mirip Tabel 19 APTIKOM
     * (bagian 7.6.4 spesifikasi).
     */
    public function show(MataKuliah $mataKuliah): View
    {
        $mataKuliah->load(['kurikulum', 'bahanKajian', 'pemetaanCpmkMk.cpmk.cpl', 'pemetaanCpmkMk.subCpmk']);

        return view('admin.mata-kuliah.show', ['mataKuliah' => $mataKuliah]);
    }

    public function edit(MataKuliah $mataKuliah): View
    {
        return view('admin.mata-kuliah.form', [
            'mataKuliah' => $mataKuliah,
            'kurikulum' => $mataKuliah->kurikulum,
        ]);
    }

    public function update(MataKuliahRequest $request, MataKuliah $mataKuliah): RedirectResponse
    {
        $mataKuliah->update($request->validated());

        return redirect()->route('admin.mata-kuliah.index')->with('sukses', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroy(MataKuliah $mataKuliah): RedirectResponse
    {
        if ($pesan = $mataKuliah->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        $mataKuliah->delete();

        return redirect()->route('admin.mata-kuliah.index')->with('sukses', 'Mata kuliah berhasil dihapus.');
    }
}
