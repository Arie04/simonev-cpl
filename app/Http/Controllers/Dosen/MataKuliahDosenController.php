<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Halaman Mata Kuliah Dosen (KF-07): CPL, CPMK, dan Sub-CPMK mata kuliah yang diampu.
 */
class MataKuliahDosenController extends Controller
{
    public function index(Request $request): View
    {
        $dosenId = $request->user()->dosen?->dosen_id;

        return view('dosen.mata-kuliah.index', [
            'daftarMataKuliah' => MataKuliah::with('kurikulum')
                ->withCount(['kelasKuliah' => fn (Builder $query) => $query->whereHas('dosen', fn (Builder $query) => $query->whereKey($dosenId))])
                ->whereHas('kelasKuliah.dosen', fn (Builder $query) => $query->whereKey($dosenId))
                ->orderBy('kode_mata_kuliah')
                ->get(),
        ]);
    }

    public function show(MataKuliah $mataKuliah): View
    {
        Gate::authorize('lihatSebagaiDosen', $mataKuliah);

        $mataKuliah->load(['kurikulum', 'pemetaanCpmkMk.cpmk.cpl', 'pemetaanCpmkMk.subCpmk']);

        return view('dosen.mata-kuliah.show', [
            'mataKuliah' => $mataKuliah,
            // CPL yang didukung MK ini lewat CPMK-nya.
            'daftarCpl' => $mataKuliah->pemetaanCpmkMk->map(fn ($pemetaan) => $pemetaan->cpmk->cpl)->unique('cpl_id')->sortBy('kode_cpl'),
        ]);
    }
}
