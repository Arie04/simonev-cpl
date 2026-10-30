<x-layouts.app judul="Akun Pengguna">
    <x-kartu>
        <x-alat-daftar :tambah="route('admin.akun.create')" label-tambah="Tambah Akun" />

        <p class="mb-4 text-sm text-text-muted">
            Akun dosen dan mahasiswa dibuat lewat menu Data Master Dosen dan Mahasiswa. Menu ini untuk
            akun Admin dan Koprodi, mengatur peran, dan mereset password.
        </p>

        @if ($daftarAkun->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada akun yang cocok dengan pencarian.' : 'Belum ada akun.'"
                :tambah="request('cari') ? null : route('admin.akun.create')"
                label-tambah="Tambah Akun"
            />
        @else
            <x-tabel :kolom="['Nama', 'Email', 'NIDN / NPM', 'Peran', 'Aksi']">
                @foreach ($daftarAkun as $akun)
                    <x-tabel.baris>
                        <x-tabel.sel label="Nama" class="font-medium">{{ $akun->nama }}</x-tabel.sel>
                        <x-tabel.sel label="Email" class="break-all">{{ $akun->email ?? '–' }}</x-tabel.sel>
                        <x-tabel.sel label="NIDN / NPM" class="whitespace-nowrap">{{ $akun->dosen?->nidn ?? $akun->mahasiswa?->nim ?? '–' }}</x-tabel.sel>
                        <x-tabel.sel label="Peran">
                            <span class="inline-flex flex-wrap justify-end gap-1 md:justify-start">
                                @foreach ($akun->roles->sortBy('nama_role') as $role)
                                    <x-badge varian="info">{{ $role->nama_role }}</x-badge>
                                @endforeach
                            </span>
                        </x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah akun '.$akun->nama" :href="route('admin.akun.edit', $akun)" />
                            <x-tombol-hapus :aksi="route('admin.akun.destroy', $akun)" :nama="$akun->nama" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <x-paginasi :data="$daftarAkun" />
        @endif
    </x-kartu>
</x-layouts.app>
