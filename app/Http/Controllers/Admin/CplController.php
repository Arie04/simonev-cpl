<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CplRequest;
use App\Models\Cpl;
use App\Models\Kurikulum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CplController extends Controller
{
    public function index(Request $request): View
    {
        $kurikulum = $this->kurikulumTerpilih($request);

        return view('admin.cpl.index', [
            'kurikulum' => $kurikulum,
            'daftarKurikulum' => Kurikulum::pilihan(),
            'daftarCpl' => Cpl::where('kurikulum_id', $kurikulum?->kurikulum_id)
                ->cari($this->kataPencarian($request))
                ->orderBy('kode_cpl')
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
        return view('admin.cpl.form', [
            'cpl' => new Cpl(['kurikulum_id' => $kurikulum->kurikulum_id]),
            'kurikulum' => $kurikulum,
        ]);
    }

    public function store(CplRequest $request): RedirectResponse
    {
        Cpl::create($request->validated());

        return redirect()->route('admin.cpl.index')->with('sukses', 'CPL berhasil ditambahkan.');
    }

    public function edit(Cpl $cpl): View
    {
        return view('admin.cpl.form', [
            'cpl' => $cpl,
            'kurikulum' => $cpl->kurikulum,
        ]);
    }

    public function update(CplRequest $request, Cpl $cpl): RedirectResponse
    {
        $cpl->update($request->validated());

        return redirect()->route('admin.cpl.index')->with('sukses', 'CPL berhasil diperbarui.');
    }

    public function destroy(Cpl $cpl): RedirectResponse
    {
        if ($pesan = $cpl->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        $cpl->delete();

        return redirect()->route('admin.cpl.index')->with('sukses', 'CPL berhasil dihapus.');
    }
}
