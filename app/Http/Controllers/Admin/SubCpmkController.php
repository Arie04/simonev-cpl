<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubCpmkRequest;
use App\Models\Kurikulum;
use App\Models\MetodePenilaian;
use App\Models\PemetaanCpmkMk;
use App\Models\SubCpmk;
use App\Support\Angka;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Data master Sub-CPMK. Sub-CPMK menempel ke pasangan CPMK–MK hasil matriks pemetaan,
 * bukan ke CPMK langsung (bagian 4 spesifikasi, KD-35).
 */
class SubCpmkController extends Controller
{
    public function index(Request $request): View
    {
        $kurikulum = $this->kurikulumTerpilih($request);

        return view('admin.sub-cpmk.index', [
            'kurikulum' => $kurikulum,
            'daftarKurikulum' => Kurikulum::pilihan(),
            // Join dipakai untuk mengurutkan berdasarkan kode MK, kode CPMK, lalu kode Sub-CPMK.
            'daftarSubCpmk' => SubCpmk::select('sub_cpmk.*')
                ->join('pemetaan_cpmk_mk', 'pemetaan_cpmk_mk.pemetaan_cpmk_mk_id', '=', 'sub_cpmk.pemetaan_cpmk_mk_id')
                ->join('mata_kuliah', 'mata_kuliah.mata_kuliah_id', '=', 'pemetaan_cpmk_mk.mata_kuliah_id')
                ->join('cpmk', 'cpmk.cpmk_id', '=', 'pemetaan_cpmk_mk.cpmk_id')
                ->where('mata_kuliah.kurikulum_id', $kurikulum?->kurikulum_id)
                ->cari($this->kataPencarian($request))
                ->with(['pemetaanCpmkMk.cpmk', 'pemetaanCpmkMk.mataKuliah'])
                ->orderBy('mata_kuliah.kode_mata_kuliah')
                ->orderBy('cpmk.kode_cpmk')
                ->orderBy('sub_cpmk.kode_sub_cpmk')
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

        $daftarPasangan = $this->daftarPasangan($kurikulum);

        if ($daftarPasangan->isEmpty()) {
            return redirect()
                ->route('admin.pemetaan.edit', ['jenis' => 'cpmk-mk'])
                ->with('gagal', 'Buat pemetaan CPMK–Mata Kuliah terlebih dahulu.');
        }

        return view('admin.sub-cpmk.form', [
            'subCpmk' => new SubCpmk,
            'kurikulum' => $kurikulum,
            'daftarPasangan' => $daftarPasangan,
        ]);
    }

    public function store(SubCpmkRequest $request): RedirectResponse
    {
        // kurikulum_id hanya dipakai untuk validasi; Sub-CPMK menempel ke pasangan CPMK–MK.
        SubCpmk::create($request->safe()->except('kurikulum_id'));

        return redirect()->route('admin.sub-cpmk.index')->with('sukses', 'Sub-CPMK berhasil ditambahkan.');
    }

    /**
     * Detail Sub-CPMK beserta rincian penilaiannya (sub_cpmk_penilaian).
     */
    public function show(SubCpmk $subCpmk): View
    {
        $subCpmk->load(['pemetaanCpmkMk.cpmk.cpl', 'pemetaanCpmkMk.mataKuliah.kurikulum', 'penilaian.metodePenilaian']);

        return view('admin.sub-cpmk.show', [
            'subCpmk' => $subCpmk,
            'daftarMetode' => MetodePenilaian::orderBy('kode_metode')->pluck('nama_metode', 'metode_penilaian_id'),
        ]);
    }

    public function edit(SubCpmk $subCpmk): View
    {
        $kurikulum = $subCpmk->pemetaanCpmkMk->mataKuliah->kurikulum;

        return view('admin.sub-cpmk.form', [
            'subCpmk' => $subCpmk,
            'kurikulum' => $kurikulum,
            'daftarPasangan' => $this->daftarPasangan($kurikulum),
        ]);
    }

    public function update(SubCpmkRequest $request, SubCpmk $subCpmk): RedirectResponse
    {
        $subCpmk->update($request->validated());

        return redirect()->route('admin.sub-cpmk.index')->with('sukses', 'Sub-CPMK berhasil diperbarui.');
    }

    public function destroy(SubCpmk $subCpmk): RedirectResponse
    {
        if ($pesan = $subCpmk->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        // Rincian penilaiannya ikut terhapus (cascade, KD-20).
        $subCpmk->delete();

        return redirect()->route('admin.sub-cpmk.index')->with('sukses', 'Sub-CPMK berhasil dihapus.');
    }

    /**
     * Pilihan pasangan CPMK–MK di kurikulum ini, mis. "INF101 Matematika Diskrit · CPMK021 (bobot 40)".
     *
     * @return Collection<string, string>
     */
    private function daftarPasangan(Kurikulum $kurikulum): Collection
    {
        return PemetaanCpmkMk::with(['cpmk', 'mataKuliah'])
            ->whereHas('mataKuliah', fn (Builder $query) => $query->where('kurikulum_id', $kurikulum->kurikulum_id))
            ->get()
            ->sortBy(fn (PemetaanCpmkMk $pemetaan) => $pemetaan->mataKuliah->kode_mata_kuliah.' '.$pemetaan->cpmk->kode_cpmk)
            ->mapWithKeys(fn (PemetaanCpmkMk $pemetaan) => [
                $pemetaan->pemetaan_cpmk_mk_id => sprintf(
                    '%s %s · %s (bobot %s)',
                    $pemetaan->mataKuliah->kode_mata_kuliah,
                    $pemetaan->mataKuliah->nama_mata_kuliah,
                    $pemetaan->cpmk->kode_cpmk,
                    Angka::ringkas($pemetaan->bobot_cpmk),
                ),
            ]);
    }
}
