@if ($daftarBahanKajian->isEmpty())
    <x-kosong pesan="Belum ada data bahan kajian." />
@else
    <x-tabel :kolom="['Kode', 'Nama Bahan Kajian', 'Kategori', 'Bobot', 'CS / KA Core', 'Referensi']">
        @foreach ($daftarBahanKajian as $bahanKajian)
            <x-tabel.baris>
                <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $bahanKajian->kode_bk }}</x-tabel.sel>
                <x-tabel.sel label="Nama Bahan Kajian" :panjang="true">{{ $bahanKajian->nama_bahan_kajian }}</x-tabel.sel>
                <x-tabel.sel label="Kategori">{{ $bahanKajian->kategori_bk ?? '–' }}</x-tabel.sel>
                <x-tabel.sel label="Bobot" class="whitespace-nowrap">{{ $bahanKajian->bobot_min === null && $bahanKajian->bobot_max === null ? '–' : ($bahanKajian->bobot_min ?? '–').'–'.($bahanKajian->bobot_max ?? '–') }}</x-tabel.sel>
                <x-tabel.sel label="CS / KA Core" class="whitespace-nowrap">{{ ($bahanKajian->cs_core ?? '–').' / '.($bahanKajian->ka_core ?? '–') }}</x-tabel.sel>
                <x-tabel.sel label="Referensi">{{ $bahanKajian->referensi ?? '–' }}</x-tabel.sel>
            </x-tabel.baris>
        @endforeach
    </x-tabel>
@endif
