<x-layouts.app judul="Kelola Data Master Profesi">
    <x-kartu>
        <x-alat-daftar
            :tambah="$kurikulum ? route('admin.profesi.create') : null"
            :daftar-kurikulum="$daftarKurikulum"
            :kurikulum="$kurikulum"
        />

        @if ($kurikulum === null)
            <x-kosong
                pesan="Belum ada kurikulum. Tambahkan kurikulum terlebih dahulu."
                :tambah="route('admin.kurikulum.create')"
                label-tambah="Tambah Kurikulum"
            />
        @elseif ($daftarProfesi->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data profesi di kurikulum ini.'"
                :tambah="request('cari') ? null : route('admin.profesi.create')"
            />
        @else
            <x-tabel :kolom="['Nama Profesi', 'Profil Lulusan', 'Aksi']">
                @foreach ($daftarProfesi as $dataProfesi)
                    <x-tabel.baris>
                        <x-tabel.sel label="Nama Profesi" class="font-medium">{{ $dataProfesi->nama_profesi }}</x-tabel.sel>
                        <x-tabel.sel label="Profil Lulusan">{{ $dataProfesi->profilLulusan->pluck('kode_pl')->sort()->implode(', ') ?: '–' }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$dataProfesi->nama_profesi" :href="route('admin.profesi.edit', $dataProfesi)" />
                            <x-tombol-hapus :aksi="route('admin.profesi.destroy', $dataProfesi)" :nama="$dataProfesi->nama_profesi" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarProfesi" />
        @endif
    </x-kartu>
</x-layouts.app>
