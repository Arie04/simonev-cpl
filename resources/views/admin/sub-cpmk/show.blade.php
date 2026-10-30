@use('App\Support\Angka')

@php($pemetaan = $subCpmk->pemetaanCpmkMk)

<x-layouts.app :judul="'Detail '.$subCpmk->kode_sub_cpmk">
    <div class="space-y-6">
        <x-kartu judul="Sub-CPMK">
            <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-[12rem_1fr]">
                <dt class="text-text-muted">Kode</dt>
                <dd class="font-medium">{{ $subCpmk->kode_sub_cpmk }}</dd>
                <dt class="text-text-muted">Deskripsi</dt>
                <dd>{{ $subCpmk->deskripsi_sub_cpmk }}</dd>
                <dt class="text-text-muted">Mata Kuliah</dt>
                <dd>{{ $pemetaan->mataKuliah->kode_mata_kuliah }} {{ $pemetaan->mataKuliah->nama_mata_kuliah }} ({{ $pemetaan->mataKuliah->kurikulum->nama_kurikulum }})</dd>
                <dt class="text-text-muted">CPMK</dt>
                <dd>{{ $pemetaan->cpmk->kode_cpmk }} – {{ $pemetaan->cpmk->deskripsi_cpmk }}</dd>
                <dt class="text-text-muted">CPL</dt>
                <dd>{{ $pemetaan->cpmk->cpl->kode_cpl }} – {{ $pemetaan->cpmk->cpl->deskripsi_cpl }}</dd>
                <dt class="text-text-muted">Bobot Sub-CPMK</dt>
                <dd>{{ Angka::ringkas($subCpmk->bobot_sub_cpmk) }}% dari nilai akhir MK (bobot CPMK {{ Angka::ringkas($pemetaan->bobot_cpmk) }}%)</dd>
                <dt class="text-text-muted">Materi</dt>
                <dd>{{ $subCpmk->materi ?? '–' }}</dd>
                <dt class="text-text-muted">Pertemuan</dt>
                <dd>
                    @if ($subCpmk->pertemuan_mulai)
                        Ke-{{ $subCpmk->pertemuan_mulai }}@if ($subCpmk->pertemuan_selesai && $subCpmk->pertemuan_selesai !== $subCpmk->pertemuan_mulai) sampai ke-{{ $subCpmk->pertemuan_selesai }}@endif
                    @else
                        –
                    @endif
                </dd>
                <dt class="text-text-muted">Catatan</dt>
                <dd>{{ $subCpmk->catatan ?? '–' }}</dd>
            </dl>

            <div class="mt-6 flex flex-col gap-3 border-t border-border pt-5 sm:flex-row sm:justify-end">
                <x-tombol varian="outline" :href="route('admin.sub-cpmk.index')">Kembali</x-tombol>
                <x-tombol varian="outline" :href="route('admin.sub-cpmk.edit', $subCpmk)">
                    <x-heroicon-o-pencil-square class="size-5" />
                    Ubah Sub-CPMK
                </x-tombol>
            </div>
        </x-kartu>

        <x-kartu judul="Rincian Penilaian">
            <p class="mb-4 text-sm text-text-muted">
                Metode penilaian yang direncanakan untuk Sub-CPMK ini. Rencana asesmen kelas yang memakai
                metode lain akan diberi peringatan (bagian 6 spesifikasi).
            </p>

            @if ($subCpmk->penilaian->isEmpty())
                <x-kosong pesan="Belum ada rincian penilaian." />
            @else
                <x-tabel :kolom="['Metode', 'Teknik', 'Instrumen', 'Kriteria', 'Aksi']">
                    @foreach ($subCpmk->penilaian->sortBy('metodePenilaian.kode_metode') as $penilaian)
                        <x-tabel.baris>
                            <x-tabel.sel label="Metode" class="font-medium">{{ $penilaian->metodePenilaian->nama_metode }}</x-tabel.sel>
                            <x-tabel.sel label="Teknik">{{ $penilaian->teknik_penilaian ?? '–' }}</x-tabel.sel>
                            <x-tabel.sel label="Instrumen">{{ $penilaian->instrumen ?? '–' }}</x-tabel.sel>
                            <x-tabel.sel label="Kriteria" :panjang="true">{{ $penilaian->kriteria ?? '–' }}</x-tabel.sel>
                            <x-tabel.aksi>
                                <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah penilaian '.$penilaian->metodePenilaian->nama_metode" :href="route('admin.sub-cpmk.penilaian.edit', [$subCpmk, $penilaian])" />
                                <x-tombol-hapus :aksi="route('admin.sub-cpmk.penilaian.destroy', [$subCpmk, $penilaian])" :nama="'penilaian '.$penilaian->metodePenilaian->nama_metode" />
                            </x-tabel.aksi>
                        </x-tabel.baris>
                    @endforeach
                </x-tabel>
            @endif

            <h3 class="mt-8 mb-4 font-semibold">Tambah Rincian Penilaian</h3>
            <form method="POST" action="{{ route('admin.sub-cpmk.penilaian.store', $subCpmk) }}" novalidate>
                @csrf
                <div class="grid gap-5 md:grid-cols-2">
                    <x-select label="Metode Penilaian" name="metode_penilaian_id" :pilihan="$daftarMetode" kosong="Pilih metode" />
                    <x-input label="Teknik Penilaian" name="teknik_penilaian" keterangan="Mis. Tes tertulis. Boleh kosong." />
                    <x-input label="Instrumen" name="instrumen" keterangan="Mis. Soal uraian. Boleh kosong." />
                    <x-textarea label="Kriteria" name="kriteria" rows="2" keterangan="Mis. Ketepatan dan kelengkapan jawaban. Boleh kosong." />
                </div>
                <div class="mt-5 flex justify-end">
                    <x-tombol type="submit">
                        <x-heroicon-o-plus class="size-5" />
                        Tambah Rincian
                    </x-tombol>
                </div>
            </form>
        </x-kartu>
    </div>
</x-layouts.app>
