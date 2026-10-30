<x-layouts.app judul="Kelola Data Master Profil Lulusan">
    <x-kartu>
        <x-alat-daftar
            :tambah="$kurikulum ? route('admin.profil-lulusan.create') : null"
            :daftar-kurikulum="$daftarKurikulum"
            :kurikulum="$kurikulum"
        />

        @if ($kurikulum === null)
            <x-kosong
                pesan="Belum ada kurikulum. Tambahkan kurikulum terlebih dahulu."
                :tambah="route('admin.kurikulum.create')"
                label-tambah="Tambah Kurikulum"
            />
        @elseif ($daftarProfilLulusan->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data profil lulusan di kurikulum ini.'"
                :tambah="request('cari') ? null : route('admin.profil-lulusan.create')"
            />
        @else
            <x-tabel :kolom="['Kode', 'Deskripsi', 'Unsur', 'Profesi', 'Aksi']">
                @foreach ($daftarProfilLulusan as $profilLulusan)
                    <x-tabel.baris>
                        <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $profilLulusan->kode_pl }}</x-tabel.sel>
                        <x-tabel.sel label="Deskripsi" :panjang="true">{{ str($profilLulusan->deskripsi_pl)->limit(160) }}</x-tabel.sel>
                        <x-tabel.sel label="Unsur">{{ $profilLulusan->unsur ?? '–' }}</x-tabel.sel>
                        <x-tabel.sel label="Profesi" :panjang="true">{{ $profilLulusan->profesi->pluck('nama_profesi')->sort()->implode(', ') ?: '–' }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$profilLulusan->kode_pl" :href="route('admin.profil-lulusan.edit', $profilLulusan)" />
                            <x-tombol-hapus :aksi="route('admin.profil-lulusan.destroy', $profilLulusan)" :nama="$profilLulusan->kode_pl" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarProfilLulusan" />
        @endif
    </x-kartu>
</x-layouts.app>
