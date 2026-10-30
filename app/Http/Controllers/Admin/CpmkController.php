<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CpmkRequest;
use App\Models\Cpmk;
use App\Models\Kurikulum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CpmkController extends Controller
{
    public function index(Request $request): View
    {
        $kurikulum = $this->kurikulumTerpilih($request);

        return view('admin.cpmk.index', [
            'kurikulum' => $kurikulum,
            'daftarKurikulum' => Kurikulum::pilihan(),
            // Tabel cpmk tidak punya kurikulum_id; kurikulumnya lewat CPL.
            'daftarCpmk' => Cpmk::with('cpl')
                ->whereHas('cpl', fn (Builder $query) => $query->where('kurikulum_id', $kurikulum?->kurikulum_id))
                ->cari($this->kataPencarian($request))
                ->orderBy('kode_cpmk')
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

        return $this->form(new Cpmk, $kurikulum);
    }

    public function store(CpmkRequest $request): RedirectResponse
    {
        // kurikulum_id hanya dipakai untuk validasi; CPMK menempel ke CPL.
        Cpmk::create($request->safe()->except('kurikulum_id'));

        return redirect()->route('admin.cpmk.index')->with('sukses', 'CPMK berhasil ditambahkan.');
    }

    public function edit(Cpmk $cpmk): View
    {
        return $this->form($cpmk, $cpmk->cpl->kurikulum);
    }

    public function update(CpmkRequest $request, Cpmk $cpmk): RedirectResponse
    {
        $cpmk->update($request->validated());

        return redirect()->route('admin.cpmk.index')->with('sukses', 'CPMK berhasil diperbarui.');
    }

    public function destroy(Cpmk $cpmk): RedirectResponse
    {
        if ($pesan = $cpmk->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        $cpmk->delete();

        return redirect()->route('admin.cpmk.index')->with('sukses', 'CPMK berhasil dihapus.');
    }

    private function form(Cpmk $cpmk, Kurikulum $kurikulum): View
    {
        return view('admin.cpmk.form', [
            'cpmk' => $cpmk,
            'kurikulum' => $kurikulum,
            'daftarCpl' => $kurikulum->cpl()->orderBy('kode_cpl')->get()
                ->mapWithKeys(fn ($cpl) => [$cpl->cpl_id => $cpl->kode_cpl.' – '.str($cpl->deskripsi_cpl)->limit(70)]),
        ]);
    }
}
