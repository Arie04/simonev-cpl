<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\KelasKuliah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menu Asesmen › Rencana Asesmen: daftar kelas yang diampu dosen, dari periode terbaru.
 */
class KelasDiampuController extends Controller
{
    public function __invoke(Request $request): View
    {
        $dosen = $request->user()->dosen;

        return view('dosen.kelas-diampu', [
            'daftarKelas' => KelasKuliah::with(['mataKuliah.kurikulum', 'periodeAkademik'])
                ->withCount(['pesertaKelas', 'rencanaAsesmen'])
                ->whereHas('dosen', fn (Builder $query) => $query->whereKey($dosen?->dosen_id))
                ->get()
                ->sortBy([
                    ['periodeAkademik.tahun_akademik', 'desc'],
                    // Genap berjalan setelah Ganjil dalam tahun akademik yang sama.
                    ['periodeAkademik.semester_periode', 'desc'],
                    ['mataKuliah.kode_mata_kuliah', 'asc'],
                ])
                ->groupBy(fn (KelasKuliah $kelas) => $kelas->periodeAkademik->nama()),
        ]);
    }
}
