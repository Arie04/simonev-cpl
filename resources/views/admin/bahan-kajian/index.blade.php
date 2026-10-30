<x-layouts.app judul="Kelola Data Master Bahan Kajian">
    <x-kartu>
        <x-alat-daftar
            :tambah="$kurikulum ? route('admin.bahan-kajian.create') : null"
            :daftar-kurikulum="$daftarKurikulum"
            :kurikulum="$kurikulum"
        />

        @if ($kurikulum === null)
            <x-kosong
                pesan="Belum ada kurikulum. Tambahkan kurikulum terlebih dahulu."
                :tambah="route('admin.kurikulum.create')"
                label-tambah="Tambah Kurikulum"
            />
        @elseif ($daftarBahanKajian->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data bahan kajian di kurikulum ini.'"
                :tambah="request('cari') ? null : route('admin.bahan-kajian.create')"
            />
        @else
            <x-tabel :kolom="['Kode', 'Nama Bahan Kajian', 'Kategori', 'Bobot', 'CS / KA Core', 'Referensi', 'Aksi']">
                @foreach ($daftarBahanKajian as $bahanKajian)
                    <x-tabel.baris>
                        <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $bahanKajian->kode_bk }}</x-tabel.sel>
                        <x-tabel.sel label="Nama Bahan Kajian" :panjang="true">{{ $bahanKajian->nama_bahan_kajian }}</x-tabel.sel>
                        <x-tabel.sel label="Kategori">{{ $bahanKajian->kategori_bk ?? '–' }}</x-tabel.sel>
                        <x-tabel.sel label="Bobot" class="whitespace-nowrap">{{ $bahanKajian->bobot_min === null && $bahanKajian->bobot_max === null ? '–' : ($bahanKajian->bobot_min ?? '–').'–'.($bahanKajian->bobot_max ?? '–') }}</x-tabel.sel>
                        <x-tabel.sel label="CS / KA Core" class="whitespace-nowrap">{{ ($bahanKajian->cs_core ?? '–').' / '.($bahanKajian->ka_core ?? '–') }}</x-tabel.sel>
                        <x-tabel.sel label="Referensi">{{ $bahanKajian->referensi ?? '–' }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$bahanKajian->kode_bk" :href="route('admin.bahan-kajian.edit', $bahanKajian)" />
                            <x-tombol-hapus :aksi="route('admin.bahan-kajian.destroy', $bahanKajian)" :nama="$bahanKajian->kode_bk" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarBahanKajian" />
        @endif
    </x-kartu>
</x-layouts.app>
