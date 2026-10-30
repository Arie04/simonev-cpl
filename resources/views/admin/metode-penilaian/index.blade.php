<x-layouts.app judul="Kelola Data Master Metode Penilaian">
    <x-kartu>
        <x-alat-daftar :tambah="route('admin.metode-penilaian.create')" />

        @if ($daftarMetodePenilaian->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data metode penilaian.'"
                :tambah="request('cari') ? null : route('admin.metode-penilaian.create')"
            />
        @else
            <x-tabel :kolom="['Kode', 'Nama Metode', 'Aksi']">
                @foreach ($daftarMetodePenilaian as $metodePenilaian)
                    <x-tabel.baris>
                        <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $metodePenilaian->kode_metode }}</x-tabel.sel>
                        <x-tabel.sel label="Nama Metode">{{ $metodePenilaian->nama_metode }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$metodePenilaian->nama_metode" :href="route('admin.metode-penilaian.edit', $metodePenilaian)" />
                            <x-tombol-hapus :aksi="route('admin.metode-penilaian.destroy', $metodePenilaian)" :nama="$metodePenilaian->nama_metode" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarMetodePenilaian" />
        @endif
    </x-kartu>
</x-layouts.app>
