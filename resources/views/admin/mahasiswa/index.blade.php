<x-layouts.app judul="Kelola Data Master Mahasiswa">
    <x-kartu>
        <x-alat-daftar :tambah="route('admin.mahasiswa.create')" />

        @if ($daftarMahasiswa->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data mahasiswa.'"
                :tambah="request('cari') ? null : route('admin.mahasiswa.create')"
            />
        @else
            <x-tabel :kolom="['NPM', 'Nama Mahasiswa', 'Angkatan', 'Aksi']">
                @foreach ($daftarMahasiswa as $dataMahasiswa)
                    <x-tabel.baris>
                        <x-tabel.sel label="NPM" class="whitespace-nowrap">{{ $dataMahasiswa->nim }}</x-tabel.sel>
                        <x-tabel.sel label="Nama Mahasiswa" class="font-medium">{{ $dataMahasiswa->nama }}</x-tabel.sel>
                        <x-tabel.sel label="Angkatan">{{ $dataMahasiswa->angkatan }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$dataMahasiswa->nama" :href="route('admin.mahasiswa.edit', $dataMahasiswa)" />
                            <x-tombol-hapus :aksi="route('admin.mahasiswa.destroy', $dataMahasiswa)" :nama="$dataMahasiswa->nama" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarMahasiswa" />
        @endif
    </x-kartu>
</x-layouts.app>
