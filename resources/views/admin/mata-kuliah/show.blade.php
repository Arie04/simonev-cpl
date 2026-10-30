@use('App\Support\Angka')

<x-layouts.app :judul="$mataKuliah->kode_mata_kuliah.' '.$mataKuliah->nama_mata_kuliah">
    <div class="space-y-6">
        <x-kartu judul="Mata Kuliah">
            <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-[12rem_1fr]">
                <dt class="text-text-muted">Kurikulum</dt>
                <dd>{{ $mataKuliah->kurikulum->nama_kurikulum }}</dd>
                <dt class="text-text-muted">Semester / SKS</dt>
                <dd>Semester {{ $mataKuliah->semester }} · {{ $mataKuliah->jumlah_sks }} SKS</dd>
                <dt class="text-text-muted">Jenis</dt>
                <dd>{{ $mataKuliah->jenis_mata_kuliah }}</dd>
                <dt class="text-text-muted">Level Bloom</dt>
                <dd>{{ $mataKuliah->level_bloom_mk ?? '–' }}</dd>
                <dt class="text-text-muted">Bahan Kajian</dt>
                <dd>
                    @forelse ($mataKuliah->bahanKajian->sortBy('kode_bk') as $bahanKajian)
                        <span class="block">{{ $bahanKajian->kode_bk }} {{ $bahanKajian->nama_bahan_kajian }} ({{ Angka::ringkas($bahanKajian->pivot->bobot_bk_mk) }}%)</span>
                    @empty
                        –
                    @endforelse
                </dd>
                <dt class="text-text-muted">Keterangan</dt>
                <dd>{{ $mataKuliah->keterangan ?? '–' }}</dd>
            </dl>

            <div class="mt-6 flex flex-col gap-3 border-t border-border pt-5 sm:flex-row sm:justify-end">
                <x-tombol varian="outline" :href="route('admin.mata-kuliah.index')">Kembali</x-tombol>
                <x-tombol varian="outline" :href="route('admin.mata-kuliah.edit', $mataKuliah)">
                    <x-heroicon-o-pencil-square class="size-5" />
                    Ubah Mata Kuliah
                </x-tombol>
            </div>
        </x-kartu>

        <x-kartu judul="Ringkasan Bobot CPMK dan Sub-CPMK">
            <p class="mb-4 text-sm text-text-muted">
                Bobot dalam persen nilai akhir mata kuliah. Bobot CPMK adalah skor maksimum CPMK (Tabel 19 APTIKOM),
                dan jumlah bobot Sub-CPMK setiap CPMK seharusnya sama dengan bobot CPMK itu.
            </p>
            <x-ringkasan-bobot-mk :mata-kuliah="$mataKuliah" />
        </x-kartu>
    </div>
</x-layouts.app>
