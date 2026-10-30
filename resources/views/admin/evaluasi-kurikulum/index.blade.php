<x-layouts.app judul="Kelola Data Master Evaluasi Kurikulum">
    <x-kartu>
        <x-alat-daftar
            :tambah="$kurikulum ? route('admin.evaluasi-kurikulum.create') : null"
            :daftar-kurikulum="$daftarKurikulum"
            :kurikulum="$kurikulum"
        />

        @if ($kurikulum === null)
            <x-kosong
                pesan="Belum ada kurikulum. Tambahkan kurikulum terlebih dahulu."
                :tambah="route('admin.kurikulum.create')"
                label-tambah="Tambah Kurikulum"
            />
        @elseif ($daftarEvaluasiKurikulum->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data evaluasi kurikulum di kurikulum ini.'"
                :tambah="request('cari') ? null : route('admin.evaluasi-kurikulum.create')"
            />
        @else
            <x-tabel :kolom="['Tahap Evaluasi', 'Kinerja Mutu', 'Standar Kinerja Mutu', 'Kesenjangan', 'Tindak Lanjut', 'Aksi']">
                @foreach ($daftarEvaluasiKurikulum as $evaluasiKurikulum)
                    <x-tabel.baris>
                        <x-tabel.sel label="Tahap Evaluasi" class="font-medium">{{ $evaluasiKurikulum->tahap_evaluasi }}</x-tabel.sel>
                        <x-tabel.sel label="Kinerja Mutu" :panjang="true">{{ str($evaluasiKurikulum->kinerja_mutu)->limit(120) }}</x-tabel.sel>
                        <x-tabel.sel label="Standar Kinerja Mutu" :panjang="true">{{ str($evaluasiKurikulum->standar_kinerja_mutu)->limit(120) }}</x-tabel.sel>
                        <x-tabel.sel label="Kesenjangan" :panjang="true">{{ str($evaluasiKurikulum->kesenjangan ?? '–')->limit(120) }}</x-tabel.sel>
                        <x-tabel.sel label="Tindak Lanjut" :panjang="true">{{ str($evaluasiKurikulum->tindak_lanjut ?? '–')->limit(120) }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$evaluasiKurikulum->tahap_evaluasi" :href="route('admin.evaluasi-kurikulum.edit', $evaluasiKurikulum)" />
                            <x-tombol-hapus :aksi="route('admin.evaluasi-kurikulum.destroy', $evaluasiKurikulum)" :nama="$evaluasiKurikulum->tahap_evaluasi" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarEvaluasiKurikulum" />
        @endif
    </x-kartu>
</x-layouts.app>
