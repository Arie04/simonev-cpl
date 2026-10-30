<x-layouts.app judul="Kelola Data Master CPL">
    <x-kartu>
        <x-alat-daftar
            :tambah="$kurikulum ? route('admin.cpl.create') : null"
            :daftar-kurikulum="$daftarKurikulum"
            :kurikulum="$kurikulum"
        />

        @if ($kurikulum === null)
            <x-kosong
                pesan="Belum ada kurikulum. Tambahkan kurikulum terlebih dahulu."
                :tambah="route('admin.kurikulum.create')"
                label-tambah="Tambah Kurikulum"
            />
        @elseif ($daftarCpl->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data CPL di kurikulum ini.'"
                :tambah="request('cari') ? null : route('admin.cpl.create')"
            />
        @else
            <x-tabel :kolom="['Kode', 'Kategori', 'Deskripsi', 'Aksi']">
                @foreach ($daftarCpl as $dataCpl)
                    <x-tabel.baris>
                        <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $dataCpl->kode_cpl }}</x-tabel.sel>
                        <x-tabel.sel label="Kategori">{{ $dataCpl->kategori_cpl }}</x-tabel.sel>
                        <x-tabel.sel label="Deskripsi" :panjang="true">{{ str($dataCpl->deskripsi_cpl)->limit(160) }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$dataCpl->kode_cpl" :href="route('admin.cpl.edit', $dataCpl)" />
                            <x-tombol-hapus :aksi="route('admin.cpl.destroy', $dataCpl)" :nama="$dataCpl->kode_cpl" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarCpl" />
        @endif
    </x-kartu>
</x-layouts.app>
