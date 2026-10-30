<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfilLulusanRequest;
use App\Models\Kurikulum;
use App\Models\ProfilLulusan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfilLulusanController extends Controller
{
    public function index(Request $request): View
    {
        $kurikulum = $this->kurikulumTerpilih($request);

        return view('admin.profil-lulusan.index', [
            'kurikulum' => $kurikulum,
            'daftarKurikulum' => Kurikulum::pilihan(),
            'daftarProfilLulusan' => ProfilLulusan::with('profesi')
                ->where('kurikulum_id', $kurikulum?->kurikulum_id)
                ->cari($this->kataPencarian($request))
                ->orderBy('kode_pl')
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

        return $this->form(new ProfilLulusan(['kurikulum_id' => $kurikulum->kurikulum_id]), $kurikulum);
    }

    public function store(ProfilLulusanRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $profilLulusan = ProfilLulusan::create($request->safe()->except('profesi'));
            $profilLulusan->profesi()->sync($request->validated('profesi', []));
        });

        return redirect()->route('admin.profil-lulusan.index')->with('sukses', 'Profil lulusan berhasil ditambahkan.');
    }

    public function edit(ProfilLulusan $profilLulusan): View
    {
        return $this->form($profilLulusan, $profilLulusan->kurikulum);
    }

    public function update(ProfilLulusanRequest $request, ProfilLulusan $profilLulusan): RedirectResponse
    {
        DB::transaction(function () use ($request, $profilLulusan) {
            $profilLulusan->update($request->safe()->except('profesi'));
            $profilLulusan->profesi()->sync($request->validated('profesi', []));
        });

        return redirect()->route('admin.profil-lulusan.index')->with('sukses', 'Profil lulusan berhasil diperbarui.');
    }

    public function destroy(ProfilLulusan $profilLulusan): RedirectResponse
    {
        // Pemetaan ke CPL dan hubungan ke profesi ikut terhapus (cascade, KD-20).
        $profilLulusan->delete();

        return redirect()->route('admin.profil-lulusan.index')->with('sukses', 'Profil lulusan berhasil dihapus.');
    }

    /**
     * Form tambah/ubah. Relasi Profil Lulusan–Profesi diisi lewat pilihan profesi dari
     * kurikulum yang sama (bagian 7.6.4 spesifikasi).
     */
    private function form(ProfilLulusan $profilLulusan, Kurikulum $kurikulum): View
    {
        return view('admin.profil-lulusan.form', [
            'profilLulusan' => $profilLulusan,
            'kurikulum' => $kurikulum,
            'daftarProfesi' => $kurikulum->profesi()->orderBy('nama_profesi')->pluck('nama_profesi', 'profesi_id'),
            'profesiTerpilih' => $profilLulusan->exists ? $profilLulusan->profesi->pluck('profesi_id')->all() : [],
        ]);
    }
}
