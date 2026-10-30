<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PemetaanRequest;
use App\Models\Kurikulum;
use App\Services\MatriksPemetaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pemetaan Kurikulum (bagian 7.6.5 spesifikasi): CPL–PL, CPL–BK, BK–MK, dan CPMK–MK
 * dalam bentuk matriks.
 */
class PemetaanController extends Controller
{
    public function edit(Request $request): View
    {
        $kurikulum = $this->kurikulumTerpilih($request);
        $jenis = MatriksPemetaan::jenisAtauBawaan($request->query('jenis'));

        return view('admin.pemetaan.edit', [
            'jenis' => $jenis,
            'kurikulum' => $kurikulum,
            'daftarKurikulum' => Kurikulum::pilihan(),
            'matriks' => $kurikulum === null ? null : new MatriksPemetaan($jenis, $kurikulum),
        ]);
    }

    public function update(PemetaanRequest $request): RedirectResponse
    {
        $matriks = $request->matriks();
        $matriks->simpan($request->validated('terhubung', []), $request->validated('bobot', []));

        return redirect()
            ->route('admin.pemetaan.edit', ['jenis' => $matriks->jenis, 'kurikulum' => $matriks->kurikulum->kurikulum_id])
            ->with('sukses', 'Pemetaan '.MatriksPemetaan::JENIS[$matriks->jenis].' berhasil disimpan.');
    }
}
