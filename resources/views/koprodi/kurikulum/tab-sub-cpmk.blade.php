@use('App\Support\Angka')

@if ($daftarSubCpmk->isEmpty())
    <x-kosong pesan="Belum ada data Sub-CPMK." />
@else
    <x-tabel :kolom="['Kode', 'CPMK · MK', 'Deskripsi', 'Bobot (%)', 'Pertemuan']">
        @foreach ($daftarSubCpmk as $subCpmk)
            <x-tabel.baris>
                <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $subCpmk->kode_sub_cpmk }}</x-tabel.sel>
                <x-tabel.sel label="CPMK · MK" class="whitespace-nowrap">{{ $subCpmk->pemetaanCpmkMk->label() }}</x-tabel.sel>
                <x-tabel.sel label="Deskripsi" :panjang="true">{{ $subCpmk->deskripsi_sub_cpmk }}</x-tabel.sel>
                <x-tabel.sel label="Bobot (%)">{{ Angka::ringkas($subCpmk->bobot_sub_cpmk) }}</x-tabel.sel>
                <x-tabel.sel label="Pertemuan" class="whitespace-nowrap">
                    {{ $subCpmk->pertemuan_mulai ? $subCpmk->pertemuan_mulai.($subCpmk->pertemuan_selesai && $subCpmk->pertemuan_selesai !== $subCpmk->pertemuan_mulai ? '–'.$subCpmk->pertemuan_selesai : '') : '–' }}
                </x-tabel.sel>
            </x-tabel.baris>
        @endforeach
    </x-tabel>
@endif
