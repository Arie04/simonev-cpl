<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluasiKurikulumRequest;
use App\Models\EvaluasiKurikulum;
use App\Models\Kurikulum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluasiKurikulumController extends Controller
{
    public function index(Request $request): View
    {
        $kurikulum = $this->kurikulumTerpilih($request);

        return view('admin.evaluasi-kurikulum.index', [
            'kurikulum' => $kurikulum,
            'daftarKurikulum' => Kurikulum::pilihan(),
            'daftarEvaluasiKurikulum' => EvaluasiKurikulum::where('kurikulum_id', $kurikulum?->kurikulum_id)
                ->cari($this->kataPencarian($request))
                ->orderBy('created_at')
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
        return view('admin.evaluasi-kurikulum.form', [
            'evaluasiKurikulum' => new EvaluasiKurikulum(['kurikulum_id' => $kurikulum->kurikulum_id]),
            'kurikulum' => $kurikulum,
        ]);
    }

    public function store(EvaluasiKurikulumRequest $request): RedirectResponse
    {
        EvaluasiKurikulum::create($request->validated());

        return redirect()->route('admin.evaluasi-kurikulum.index')->with('sukses', 'Evaluasi kurikulum berhasil ditambahkan.');
    }

    public function edit(EvaluasiKurikulum $evaluasiKurikulum): View
    {
        return view('admin.evaluasi-kurikulum.form', [
            'evaluasiKurikulum' => $evaluasiKurikulum,
            'kurikulum' => $evaluasiKurikulum->kurikulum,
        ]);
    }

    public function update(EvaluasiKurikulumRequest $request, EvaluasiKurikulum $evaluasiKurikulum): RedirectResponse
    {
        $evaluasiKurikulum->update($request->validated());

        return redirect()->route('admin.evaluasi-kurikulum.index')->with('sukses', 'Evaluasi kurikulum berhasil diperbarui.');
    }

    public function destroy(EvaluasiKurikulum $evaluasiKurikulum): RedirectResponse
    {
        $evaluasiKurikulum->delete();

        return redirect()->route('admin.evaluasi-kurikulum.index')->with('sukses', 'Evaluasi kurikulum berhasil dihapus.');
    }
}
