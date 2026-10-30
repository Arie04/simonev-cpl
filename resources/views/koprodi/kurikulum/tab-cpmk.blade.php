@use('App\Support\Angka')

@if ($daftarCpmk->isEmpty())
    <x-kosong pesan="Belum ada data CPMK." />
@else
    <x-tabel :kolom="['Kode', 'CPL', 'Deskripsi', 'Mata Kuliah (bobot)']">
        @foreach ($daftarCpmk as $cpmk)
            <x-tabel.baris>
                <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $cpmk->kode_cpmk }}</x-tabel.sel>
                <x-tabel.sel label="CPL" class="whitespace-nowrap">{{ $cpmk->cpl->kode_cpl }}</x-tabel.sel>
                <x-tabel.sel label="Deskripsi" :panjang="true">{{ $cpmk->deskripsi_cpmk }}</x-tabel.sel>
                <x-tabel.sel label="Mata Kuliah (bobot)">
                    {{ $cpmk->mataKuliah->sortBy('kode_mata_kuliah')->map(fn ($mataKuliah) => $mataKuliah->kode_mata_kuliah.' ('.Angka::ringkas($mataKuliah->pivot->bobot_cpmk).')')->implode(', ') ?: '–' }}
                </x-tabel.sel>
            </x-tabel.baris>
        @endforeach
    </x-tabel>
@endif
