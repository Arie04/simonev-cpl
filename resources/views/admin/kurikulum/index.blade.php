<x-layouts.app judul="Kelola Data Master Kurikulum">
    <x-kartu>
        <x-alat-daftar :tambah="route('admin.kurikulum.create')" />

        @if ($daftarKurikulum->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data kurikulum.'"
                :tambah="request('cari') ? null : route('admin.kurikulum.create')"
            />
        @else
            <x-tabel :kolom="['Nama Kurikulum', 'Tahun Berlaku', 'Program Studi', 'Aksi']">
                @foreach ($daftarKurikulum as $dataKurikulum)
                    <x-tabel.baris>
                        <x-tabel.sel label="Nama Kurikulum" class="font-medium">{{ $dataKurikulum->nama_kurikulum }}</x-tabel.sel>
                        <x-tabel.sel label="Tahun Berlaku">{{ $dataKurikulum->tahun_berlaku }}</x-tabel.sel>
                        <x-tabel.sel label="Program Studi">{{ $dataKurikulum->programStudi->nama_program_studi }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$dataKurikulum->nama_kurikulum" :href="route('admin.kurikulum.edit', $dataKurikulum)" />
                            <x-tombol-hapus :aksi="route('admin.kurikulum.destroy', $dataKurikulum)" :nama="$dataKurikulum->nama_kurikulum" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarKurikulum" />
        @endif
    </x-kartu>
</x-layouts.app>
