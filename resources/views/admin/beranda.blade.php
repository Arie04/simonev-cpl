<x-layouts.app judul="Beranda">
    <p class="-mt-3 mb-6 text-text-muted">Selamat datang, {{ auth()->user()->nama }}.</p>

    {{-- Kartu statistik: 2 kolom di mobile, 4 kolom di desktop (bagian 7.4). --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-kartu-statistik ikon="heroicon-o-academic-cap" label="Mahasiswa" :nilai="$jumlahMahasiswa" />
        <x-kartu-statistik ikon="heroicon-o-user-group" label="Dosen" :nilai="$jumlahDosen" />
        <x-kartu-statistik
            ikon="heroicon-o-book-open"
            label="Mata Kuliah"
            :nilai="$jumlahMataKuliah"
            :keterangan="$kurikulumTerbaru ? $kurikulumTerbaru->nama_kurikulum : 'Belum ada kurikulum'"
        />
        <x-kartu-statistik
            ikon="heroicon-o-building-library"
            label="Kelas"
            :nilai="$jumlahKelas"
            :keterangan="$periodeAktif ? 'Periode '.$periodeAktif->nama() : 'Belum ada kelas'"
        />
    </div>

    <x-kartu judul="Pintasan" class="mt-6">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['label' => 'Data Mahasiswa', 'route' => 'admin.mahasiswa.index', 'ikon' => 'heroicon-o-academic-cap'],
                ['label' => 'Data Dosen', 'route' => 'admin.dosen.index', 'ikon' => 'heroicon-o-user-group'],
                ['label' => 'Mata Kuliah', 'route' => 'admin.mata-kuliah.index', 'ikon' => 'heroicon-o-book-open'],
                ['label' => 'Pemetaan Kurikulum', 'route' => 'admin.pemetaan.edit', 'ikon' => 'heroicon-o-table-cells'],
                ['label' => 'Akun Pengguna', 'route' => 'admin.akun.index', 'ikon' => 'heroicon-o-users'],
            ] as $pintasan)
                <a
                    href="{{ route($pintasan['route']) }}"
                    class="flex min-h-11 items-center gap-3 rounded-lg border border-border px-4 py-3 text-sm font-medium hover:border-primary-700 hover:bg-primary-50 hover:text-primary-900"
                >
                    <x-dynamic-component :component="$pintasan['ikon']" class="size-5 text-primary-700" />
                    {{ $pintasan['label'] }}
                </a>
            @endforeach
        </div>
    </x-kartu>
</x-layouts.app>
