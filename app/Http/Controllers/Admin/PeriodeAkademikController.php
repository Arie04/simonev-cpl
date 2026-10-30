<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PeriodeAkademikRequest;
use App\Models\PeriodeAkademik;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeriodeAkademikController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.periode-akademik.index', [
            'daftarPeriode' => PeriodeAkademik::withCount('kelasKuliah')
                ->cari($this->kataPencarian($request))
                ->urutTerbaru()
                ->paginate($this->jumlahPerHalaman($request))
                ->withQueryString(),
            'periodeAktif' => PeriodeAkademik::aktif(),
        ]);
    }

    public function create(): View
    {
        return view('admin.periode-akademik.form', ['periodeAkademik' => new PeriodeAkademik]);
    }

    public function store(PeriodeAkademikRequest $request): RedirectResponse
    {
        PeriodeAkademik::create($request->validated());

        return redirect()->route('admin.periode-akademik.index')->with('sukses', 'Periode akademik berhasil ditambahkan.');
    }

    public function edit(PeriodeAkademik $periodeAkademik): View
    {
        return view('admin.periode-akademik.form', ['periodeAkademik' => $periodeAkademik]);
    }

    public function update(PeriodeAkademikRequest $request, PeriodeAkademik $periodeAkademik): RedirectResponse
    {
        $periodeAkademik->update($request->validated());

        return redirect()->route('admin.periode-akademik.index')->with('sukses', 'Periode akademik berhasil diperbarui.');
    }

    public function destroy(PeriodeAkademik $periodeAkademik): RedirectResponse
    {
        if ($pesan = $periodeAkademik->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        $periodeAkademik->delete();

        return redirect()->route('admin.periode-akademik.index')->with('sukses', 'Periode akademik berhasil dihapus.');
    }
}
