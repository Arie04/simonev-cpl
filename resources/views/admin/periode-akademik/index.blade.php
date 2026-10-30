<x-layouts.app judul="Periode Akademik">
    <x-kartu>
        <x-alat-daftar :tambah="route('admin.periode-akademik.create')" label-tambah="Tambah Periode" />

        @if ($daftarPeriode->isEmpty())
            <x-kosong
                :pesan="request('cari') ? 'Tidak ada periode yang cocok dengan pencarian.' : 'Belum ada periode akademik.'"
                :tambah="request('cari') ? null : route('admin.periode-akademik.create')"
                label-tambah="Tambah Periode"
            />
        @else
            <x-tabel :kolom="['Tahun Akademik', 'Semester', 'Jumlah Kelas', 'Aksi']">
                @foreach ($daftarPeriode as $periode)
                    <x-tabel.baris>
                        <x-tabel.sel label="Tahun Akademik" class="font-medium">
                            {{ $periode->tahun_akademik }}
                            @if ($periodeAktif?->is($periode))
                                <x-badge varian="info" class="ml-1">Aktif</x-badge>
                            @endif
                        </x-tabel.sel>
                        <x-tabel.sel label="Semester">{{ $periode->semester_periode }}</x-tabel.sel>
                        <x-tabel.sel label="Jumlah Kelas">{{ $periode->kelas_kuliah_count }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah periode '.$periode->nama()" :href="route('admin.periode-akademik.edit', $periode)" />
                            <x-tombol-hapus :aksi="route('admin.periode-akademik.destroy', $periode)" :nama="$periode->nama()" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>

            <p class="mt-4 text-xs text-text-muted">Periode aktif = periode terbaru yang sudah memiliki kelas.</p>

            <x-paginasi :data="$daftarPeriode" />
        @endif
    </x-kartu>
</x-layouts.app>
