@use('App\Support\Angka')

<x-layouts.app judul="Kelola Data Master Sub-CPMK">
    <x-kartu>
        <x-alat-daftar
            :tambah="$kurikulum ? route('admin.sub-cpmk.create') : null"
            :daftar-kurikulum="$daftarKurikulum"
            :kurikulum="$kurikulum"
        />

        @if ($kurikulum === null)
            <x-kosong
                pesan="Belum ada kurikulum. Tambahkan kurikulum terlebih dahulu."
                :tambah="route('admin.kurikulum.create')"
                label-tambah="Tambah Kurikulum"
            />
        @elseif ($daftarSubCpmk->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data Sub-CPMK di kurikulum ini.'"
                :tambah="request('cari') ? null : route('admin.sub-cpmk.create')"
            />
        @else
            <x-tabel :kolom="['Kode', 'CPMK · MK', 'Deskripsi', 'Bobot (%)', 'Pertemuan', 'Aksi']">
                @foreach ($daftarSubCpmk as $subCpmk)
                    <x-tabel.baris>
                        <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $subCpmk->kode_sub_cpmk }}</x-tabel.sel>
                        <x-tabel.sel label="CPMK · MK" class="whitespace-nowrap">{{ $subCpmk->pemetaanCpmkMk->label() }}</x-tabel.sel>
                        <x-tabel.sel label="Deskripsi" :panjang="true">{{ str($subCpmk->deskripsi_sub_cpmk)->limit(140) }}</x-tabel.sel>
                        <x-tabel.sel label="Bobot (%)">{{ Angka::ringkas($subCpmk->bobot_sub_cpmk) }}</x-tabel.sel>
                        <x-tabel.sel label="Pertemuan" class="whitespace-nowrap">
                            @if ($subCpmk->pertemuan_mulai)
                                {{ $subCpmk->pertemuan_mulai }}@if ($subCpmk->pertemuan_selesai && $subCpmk->pertemuan_selesai !== $subCpmk->pertemuan_mulai)–{{ $subCpmk->pertemuan_selesai }}@endif
                            @else
                                –
                            @endif
                        </x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-eye" :label="'Lihat detail '.$subCpmk->kode_sub_cpmk" :href="route('admin.sub-cpmk.show', $subCpmk)" />
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$subCpmk->kode_sub_cpmk" :href="route('admin.sub-cpmk.edit', $subCpmk)" />
                            <x-tombol-hapus :aksi="route('admin.sub-cpmk.destroy', $subCpmk)" :nama="$subCpmk->kode_sub_cpmk.' · '.$subCpmk->pemetaanCpmkMk->mataKuliah->kode_mata_kuliah" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarSubCpmk" />
        @endif
    </x-kartu>
</x-layouts.app>
