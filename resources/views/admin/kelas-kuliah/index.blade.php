<x-layouts.app judul="Kelas Kuliah">
    <x-kartu>
        <x-alat-daftar
            :tambah="$periode ? route('admin.kelas-kuliah.create') : null"
            label-tambah="Tambah Kelas"
            :daftar-periode="$daftarPilihanPeriode"
            :periode="$periode"
        />

        @if ($periode === null)
            <x-kosong
                pesan="Belum ada periode akademik. Tambahkan periode terlebih dahulu."
                :tambah="route('admin.periode-akademik.create')"
                label-tambah="Tambah Periode"
            />
        @elseif ($daftarKelas->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada kelas yang cocok dengan pencarian.' : 'Belum ada kelas pada periode ini.'"
                :tambah="request('cari') ? null : route('admin.kelas-kuliah.create')"
                label-tambah="Tambah Kelas"
            />
        @else
            <x-tabel :kolom="['Mata Kuliah', 'Kelas', 'Dosen', 'Peserta', 'Aksi']">
                @foreach ($daftarKelas as $kelas)
                    <x-tabel.baris>
                        <x-tabel.sel label="Mata Kuliah" :panjang="true">
                            <span class="font-medium">{{ $kelas->mataKuliah->kode_mata_kuliah }}</span>
                            {{ $kelas->mataKuliah->nama_mata_kuliah }}
                            <span class="block text-xs text-text-muted">{{ $kelas->mataKuliah->kurikulum->nama_kurikulum }}</span>
                        </x-tabel.sel>
                        <x-tabel.sel label="Kelas">{{ $kelas->nama_kelas }}</x-tabel.sel>
                        <x-tabel.sel label="Dosen" :panjang="true">{{ $kelas->dosen->pluck('nama')->sort()->implode(', ') ?: '–' }}</x-tabel.sel>
                        <x-tabel.sel label="Peserta">{{ $kelas->peserta_kelas_count }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-eye" :label="'Lihat detail kelas '.$kelas->nama()" :href="route('admin.kelas-kuliah.show', $kelas)" />
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah kelas '.$kelas->nama()" :href="route('admin.kelas-kuliah.edit', $kelas)" />
                            <x-tombol-hapus :aksi="route('admin.kelas-kuliah.destroy', $kelas)" :nama="$kelas->nama()" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarKelas" />
        @endif
    </x-kartu>
</x-layouts.app>
