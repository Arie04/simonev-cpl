<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProgramStudiRequest;
use App\Models\ProgramStudi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramStudiController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.program-studi.index', [
            'daftarProgramStudi' => ProgramStudi::cari($this->kataPencarian($request))
                ->orderBy('nama_program_studi')
                ->paginate($this->jumlahPerHalaman($request))
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.program-studi.form', ['programStudi' => new ProgramStudi]);
    }

    public function store(ProgramStudiRequest $request): RedirectResponse
    {
        ProgramStudi::create($request->validated());

        return redirect()->route('admin.program-studi.index')->with('sukses', 'Program studi berhasil ditambahkan.');
    }

    public function edit(ProgramStudi $programStudi): View
    {
        return view('admin.program-studi.form', ['programStudi' => $programStudi]);
    }

    public function update(ProgramStudiRequest $request, ProgramStudi $programStudi): RedirectResponse
    {
        $programStudi->update($request->validated());

        return redirect()->route('admin.program-studi.index')->with('sukses', 'Program studi berhasil diperbarui.');
    }

    public function destroy(ProgramStudi $programStudi): RedirectResponse
    {
        if ($pesan = $programStudi->alasanTidakBisaDihapus()) {
            return back()->with('gagal', $pesan);
        }

        $programStudi->delete();

        return redirect()->route('admin.program-studi.index')->with('sukses', 'Program studi berhasil dihapus.');
    }
}
