<x-layouts.app judul="Kelola Data Master Mata Kuliah">
    <x-kartu>
        <x-alat-daftar
            :tambah="$kurikulum ? route('admin.mata-kuliah.create') : null"
            :daftar-kurikulum="$daftarKurikulum"
            :kurikulum="$kurikulum"
        />

        @if ($kurikulum === null)
            <x-kosong
                pesan="Belum ada kurikulum. Tambahkan kurikulum terlebih dahulu."
                :tambah="route('admin.kurikulum.create')"
                label-tambah="Tambah Kurikulum"
            />
        @elseif ($daftarMataKuliah->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data mata kuliah di kurikulum ini.'"
                :tambah="request('cari') ? null : route('admin.mata-kuliah.create')"
            />
        @else
            <x-tabel :kolom="['Kode', 'Nama Mata Kuliah', 'Semester', 'SKS', 'Jenis', 'Aksi']">
                @foreach ($daftarMataKuliah as $mataKuliah)
                    <x-tabel.baris>
                        <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $mataKuliah->kode_mata_kuliah }}</x-tabel.sel>
                        <x-tabel.sel label="Nama Mata Kuliah" :panjang="true">{{ $mataKuliah->nama_mata_kuliah }}</x-tabel.sel>
                        <x-tabel.sel label="Semester">{{ $mataKuliah->semester }}</x-tabel.sel>
                        <x-tabel.sel label="SKS">{{ $mataKuliah->jumlah_sks }}</x-tabel.sel>
                        <x-tabel.sel label="Jenis">{{ $mataKuliah->jenis_mata_kuliah }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-eye" :label="'Lihat detail '.$mataKuliah->kode_mata_kuliah" :href="route('admin.mata-kuliah.show', $mataKuliah)" />
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$mataKuliah->kode_mata_kuliah" :href="route('admin.mata-kuliah.edit', $mataKuliah)" />
                            <x-tombol-hapus :aksi="route('admin.mata-kuliah.destroy', $mataKuliah)" :nama="$mataKuliah->kode_mata_kuliah" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarMataKuliah" />
        @endif
    </x-kartu>
</x-layouts.app>
