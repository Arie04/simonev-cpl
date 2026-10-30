<x-layouts.app judul="Kelola Data Master Dosen">
    <x-kartu>
        <x-alat-daftar :tambah="route('admin.dosen.create')" />

        @if ($daftarDosen->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data dosen.'"
                :tambah="request('cari') ? null : route('admin.dosen.create')"
            />
        @else
            <x-tabel :kolom="['NIDN', 'Nama Dosen', 'Email', 'Aksi']">
                @foreach ($daftarDosen as $dataDosen)
                    <x-tabel.baris>
                        <x-tabel.sel label="NIDN" class="whitespace-nowrap">{{ $dataDosen->nidn }}</x-tabel.sel>
                        <x-tabel.sel label="Nama Dosen" class="font-medium">{{ $dataDosen->nama }}</x-tabel.sel>
                        <x-tabel.sel label="Email">{{ $dataDosen->user->email ?? '–' }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$dataDosen->nama" :href="route('admin.dosen.edit', $dataDosen)" />
                            <x-tombol-hapus :aksi="route('admin.dosen.destroy', $dataDosen)" :nama="$dataDosen->nama" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarDosen" />
        @endif
    </x-kartu>
</x-layouts.app>
