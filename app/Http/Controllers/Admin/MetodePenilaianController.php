<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MetodePenilaianRequest;
use App\Models\MetodePenilaian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetodePenilaianController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.metode-penilaian.index', [
            'daftarMetodePenilaian' => MetodePenilaian::cari($this->kataPencarian($request))
                ->orderBy('kode_metode')
                ->paginate($this->jumlahPerHalaman($request))
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.metode-penilaian.form', ['metodePenilaian' => new MetodePenilaian]);
    }

    public function store(MetodePenilaianRequest $request): RedirectResponse
    {
        MetodePenilaian::create($request->validated());

        return redirect()->route('admin.metode-penilaian.index')->with('sukses', 'Metode penilaian berhasil ditambahkan.');
    }

    public function edit(MetodePenilaian $metodePenilaian): View
    {
        return view('admin.metode-penilaian.form', ['metodePenilaian' => $metodePenilaian]);
    }

    public function update(MetodePenilaianRequest $request, MetodePenilaian $metodePenilaian): RedirectResponse
    {
        $metodePenilaian->update($request->validated());

        return redirect()->route('admin.metode-penilaian.index')->with('sukses', 'Metode penilaian berhasil diperbarui.');
    }

    public function destroy(MetodePenilaian $metodePenilaian): RedirectResponse
    {
        if ($pesan = $metodePenilaian->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        $metodePenilaian->delete();

        return redirect()->route('admin.metode-penilaian.index')->with('sukses', 'Metode penilaian berhasil dihapus.');
    }
}
