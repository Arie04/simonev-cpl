<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BahanKajianRequest;
use App\Models\BahanKajian;
use App\Models\Kurikulum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BahanKajianController extends Controller
{
    public function index(Request $request): View
    {
        $kurikulum = $this->kurikulumTerpilih($request);

        return view('admin.bahan-kajian.index', [
            'kurikulum' => $kurikulum,
            'daftarKurikulum' => Kurikulum::pilihan(),
            'daftarBahanKajian' => BahanKajian::where('kurikulum_id', $kurikulum?->kurikulum_id)
                ->cari($this->kataPencarian($request))
                ->orderBy('kode_bk')
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
        return view('admin.bahan-kajian.form', [
            'bahanKajian' => new BahanKajian(['kurikulum_id' => $kurikulum->kurikulum_id]),
            'kurikulum' => $kurikulum,
        ]);
    }

    public function store(BahanKajianRequest $request): RedirectResponse
    {
        BahanKajian::create($request->validated());

        return redirect()->route('admin.bahan-kajian.index')->with('sukses', 'Bahan kajian berhasil ditambahkan.');
    }

    public function edit(BahanKajian $bahanKajian): View
    {
        return view('admin.bahan-kajian.form', [
            'bahanKajian' => $bahanKajian,
            'kurikulum' => $bahanKajian->kurikulum,
        ]);
    }

    public function update(BahanKajianRequest $request, BahanKajian $bahanKajian): RedirectResponse
    {
        $bahanKajian->update($request->validated());

        return redirect()->route('admin.bahan-kajian.index')->with('sukses', 'Bahan kajian berhasil diperbarui.');
    }

    public function destroy(BahanKajian $bahanKajian): RedirectResponse
    {
        // Pemetaan BK ke CPL dan MK ikut terhapus (cascade, KD-20).
        $bahanKajian->delete();

        return redirect()->route('admin.bahan-kajian.index')->with('sukses', 'Bahan kajian berhasil dihapus.');
    }
}
