@if ($daftarProfilLulusan->isEmpty())
    <x-kosong pesan="Belum ada data profil lulusan." />
@else
    <x-tabel :kolom="['Kode', 'Deskripsi', 'Unsur', 'Sumber', 'Profesi']">
        @foreach ($daftarProfilLulusan as $profilLulusan)
            <x-tabel.baris>
                <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $profilLulusan->kode_pl }}</x-tabel.sel>
                <x-tabel.sel label="Deskripsi" :panjang="true">{{ $profilLulusan->deskripsi_pl }}</x-tabel.sel>
                <x-tabel.sel label="Unsur">{{ $profilLulusan->unsur ?? '–' }}</x-tabel.sel>
                <x-tabel.sel label="Sumber" :panjang="true">{{ $profilLulusan->sumber ?? '–' }}</x-tabel.sel>
                <x-tabel.sel label="Profesi" :panjang="true">{{ $profilLulusan->profesi->pluck('nama_profesi')->sort()->implode(', ') ?: '–' }}</x-tabel.sel>
            </x-tabel.baris>
        @endforeach
    </x-tabel>
@endif
