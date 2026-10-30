@if ($daftarCpl->isEmpty())
    <x-kosong pesan="Belum ada data CPL." />
@else
    <x-tabel :kolom="['Kode', 'Kategori', 'Deskripsi', 'CPMK']">
        @foreach ($daftarCpl as $cpl)
            <x-tabel.baris>
                <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $cpl->kode_cpl }}</x-tabel.sel>
                <x-tabel.sel label="Kategori">{{ $cpl->kategori_cpl }}</x-tabel.sel>
                <x-tabel.sel label="Deskripsi" :panjang="true">{{ $cpl->deskripsi_cpl }}</x-tabel.sel>
                <x-tabel.sel label="CPMK">{{ $cpl->cpmk->pluck('kode_cpmk')->sort()->implode(', ') ?: '–' }}</x-tabel.sel>
            </x-tabel.baris>
        @endforeach
    </x-tabel>
@endif
