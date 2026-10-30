<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KurikulumRequest;
use App\Models\Kurikulum;
use App\Models\ProgramStudi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KurikulumController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.kurikulum.index', [
            'daftarKurikulum' => Kurikulum::with('programStudi')
                ->cari($this->kataPencarian($request))
                ->orderByDesc('tahun_berlaku')
                ->paginate($this->jumlahPerHalaman($request))
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Kurikulum);
    }

    public function store(KurikulumRequest $request): RedirectResponse
    {
        Kurikulum::create($request->validated());

        return redirect()->route('admin.kurikulum.index')->with('sukses', 'Kurikulum berhasil ditambahkan.');
    }

    public function edit(Kurikulum $kurikulum): View
    {
        return $this->form($kurikulum);
    }

    public function update(KurikulumRequest $request, Kurikulum $kurikulum): RedirectResponse
    {
        $kurikulum->update($request->validated());

        return redirect()->route('admin.kurikulum.index')->with('sukses', 'Kurikulum berhasil diperbarui.');
    }

    public function destroy(Kurikulum $kurikulum): RedirectResponse
    {
        if ($pesan = $kurikulum->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        $kurikulum->delete();

        return redirect()->route('admin.kurikulum.index')->with('sukses', 'Kurikulum berhasil dihapus.');
    }

    private function form(Kurikulum $kurikulum): View
    {
        return view('admin.kurikulum.form', [
            'kurikulum' => $kurikulum,
            'daftarProgramStudi' => ProgramStudi::orderBy('nama_program_studi')->pluck('nama_program_studi', 'program_studi_id'),
        ]);
    }
}
