<x-layouts.app judul="Kelola Data Master Program Studi">
    <x-kartu>
        <x-alat-daftar :tambah="route('admin.program-studi.create')" />

        @if ($daftarProgramStudi->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data program studi.'"
                :tambah="request('cari') ? null : route('admin.program-studi.create')"
            />
        @else
            <x-tabel :kolom="['Nama Program Studi', 'Jenjang', 'Fakultas', 'Akreditasi', 'Aksi']">
                @foreach ($daftarProgramStudi as $programStudi)
                    <x-tabel.baris>
                        <x-tabel.sel label="Nama Program Studi" class="font-medium">{{ $programStudi->nama_program_studi }}</x-tabel.sel>
                        <x-tabel.sel label="Jenjang">{{ $programStudi->jenjang_pendidikan }}</x-tabel.sel>
                        <x-tabel.sel label="Fakultas">{{ $programStudi->nama_fakultas }}</x-tabel.sel>
                        <x-tabel.sel label="Akreditasi">{{ $programStudi->peringkat_akreditasi ?? '–' }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$programStudi->nama_program_studi" :href="route('admin.program-studi.edit', $programStudi)" />
                            <x-tombol-hapus :aksi="route('admin.program-studi.destroy', $programStudi)" :nama="$programStudi->nama_program_studi" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarProgramStudi" />
        @endif
    </x-kartu>
</x-layouts.app>
