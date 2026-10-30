@use('App\Support\Angka')

{{-- Tabel capaian (Sub-CPMK, CPMK, atau CPL) seorang mahasiswa dengan badge Lulus/Belum Lulus. --}}
@if ($baris->isEmpty())
    <x-kosong pesan="Belum ada data." />
@else
    <x-tabel :kolom="['Kode', 'Deskripsi', 'Capaian (%)', 'Status']">
        @foreach ($baris as $satuBaris)
            <x-tabel.baris>
                <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $satuBaris['kode'] }}</x-tabel.sel>
                <x-tabel.sel label="Deskripsi" :panjang="true">{{ $satuBaris['deskripsi'] }}</x-tabel.sel>
                <x-tabel.sel label="Capaian (%)" class="whitespace-nowrap">{{ $satuBaris['capaian'] !== null ? Angka::format($satuBaris['capaian']) : 'Belum ada nilai' }}</x-tabel.sel>
                <x-tabel.sel label="Status"><x-status-lulus :capaian="$satuBaris['capaian']" :lulus="$satuBaris['lulus']" /></x-tabel.sel>
            </x-tabel.baris>
        @endforeach
    </x-tabel>
@endif
