<x-layouts.app judul="Kelola Data Master CPMK">
    <x-kartu>
        <x-alat-daftar
            :tambah="$kurikulum ? route('admin.cpmk.create') : null"
            :daftar-kurikulum="$daftarKurikulum"
            :kurikulum="$kurikulum"
        />

        @if ($kurikulum === null)
            <x-kosong
                pesan="Belum ada kurikulum. Tambahkan kurikulum terlebih dahulu."
                :tambah="route('admin.kurikulum.create')"
                label-tambah="Tambah Kurikulum"
            />
        @elseif ($daftarCpmk->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data CPMK di kurikulum ini.'"
                :tambah="request('cari') ? null : route('admin.cpmk.create')"
            />
        @else
            <x-tabel :kolom="['Kode', 'CPL', 'Deskripsi', 'Aksi']">
                @foreach ($daftarCpmk as $dataCpmk)
                    <x-tabel.baris>
                        <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $dataCpmk->kode_cpmk }}</x-tabel.sel>
                        <x-tabel.sel label="CPL" class="whitespace-nowrap">{{ $dataCpmk->cpl->kode_cpl }}</x-tabel.sel>
                        <x-tabel.sel label="Deskripsi" :panjang="true">{{ str($dataCpmk->deskripsi_cpmk)->limit(160) }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$dataCpmk->kode_cpmk" :href="route('admin.cpmk.edit', $dataCpmk)" />
                            <x-tombol-hapus :aksi="route('admin.cpmk.destroy', $dataCpmk)" :nama="$dataCpmk->kode_cpmk" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarCpmk" />
        @endif
    </x-kartu>
</x-layouts.app>
