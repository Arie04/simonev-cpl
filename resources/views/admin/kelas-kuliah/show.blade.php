<x-layouts.app :judul="'Kelas '.$kelas->nama()">
    <div class="space-y-6">
        {{-- Kartu info kelas --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-kartu-statistik ikon="heroicon-o-book-open" label="Mata Kuliah" :nilai="$kelas->mataKuliah->kode_mata_kuliah" :keterangan="$kelas->mataKuliah->nama_mata_kuliah.' · '.$kelas->mataKuliah->kurikulum->nama_kurikulum" />
            <x-kartu-statistik ikon="heroicon-o-user-group" label="Kelas" :nilai="$kelas->nama_kelas" :keterangan="$daftarPeserta->count().' peserta'" />
            <x-kartu-statistik ikon="heroicon-o-calendar-days" label="Periode" :nilai="$kelas->periodeAkademik->tahun_akademik" :keterangan="'Semester '.$kelas->periodeAkademik->semester_periode" />
        </div>

        <x-kartu judul="Dosen Pengampu">
            @if ($kelas->dosen->isEmpty())
                <x-kosong pesan="Belum ada dosen pengampu." />
            @else
                <ul class="divide-y divide-border rounded-lg border border-border">
                    @foreach ($kelas->dosen->sortBy('nama') as $dosen)
                        <li class="flex items-center justify-between gap-3 px-4 py-2">
                            <span class="min-w-0">
                                <span class="block font-medium">{{ $dosen->nama }}</span>
                                <span class="text-xs text-text-muted">NIDN {{ $dosen->nidn }}</span>
                            </span>
                            <x-tombol-hapus :aksi="route('admin.kelas-kuliah.dosen.destroy', [$kelas, $dosen])" :nama="$dosen->nama.' dari pengampu kelas ini'" />
                        </li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('admin.kelas-kuliah.dosen.store', $kelas) }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end" novalidate>
                @csrf
                <div class="flex-1">
                    <x-select label="Tambah Dosen Pengampu" name="dosen_id" :pilihan="$pilihanDosen" kosong="Pilih dosen" />
                </div>
                <x-tombol varian="outline" type="submit">
                    <x-heroicon-o-plus class="size-5" />
                    Tambah Dosen
                </x-tombol>
            </form>
        </x-kartu>

        <x-kartu judul="Peserta Kelas">
            <div class="grid gap-6 lg:grid-cols-2">
                {{-- Tambah sekaligus per angkatan (bagian 7.6.6). --}}
                <form method="POST" action="{{ route('admin.kelas-kuliah.peserta-angkatan.store', $kelas) }}" class="flex flex-col gap-3 sm:flex-row sm:items-end" novalidate>
                    @csrf
                    <div class="flex-1">
                        <x-select label="Tambah per Angkatan" name="angkatan" :pilihan="$pilihanAngkatan" kosong="Pilih angkatan" />
                    </div>
                    <x-tombol varian="outline" type="submit">
                        <x-heroicon-o-user-plus class="size-5" />
                        Tambah Angkatan
                    </x-tombol>
                </form>

                {{-- Cari satu mahasiswa berdasarkan NPM atau nama. --}}
                <form method="GET" action="{{ route('admin.kelas-kuliah.show', $kelas) }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <x-input label="Cari Mahasiswa" name="cari_mahasiswa" type="search" :value="request('cari_mahasiswa')" placeholder="NPM atau nama" ikon="heroicon-o-magnifying-glass" />
                    </div>
                    <x-tombol varian="outline" type="submit">Cari</x-tombol>
                </form>
            </div>

            @error('mahasiswa_id')
                <p class="mt-4 rounded-lg bg-danger-bg px-4 py-3 text-sm text-danger">{{ $message }}</p>
            @enderror

            @if ($hasilCari !== null)
                <div class="mt-4 rounded-lg border border-border">
                    <p class="border-b border-border bg-neutral-bg px-4 py-2 text-xs font-semibold text-text-muted">
                        Hasil pencarian "{{ request('cari_mahasiswa') }}" (mahasiswa yang belum mengikuti mata kuliah ini pada periode ini)
                    </p>
                    @forelse ($hasilCari as $mahasiswa)
                        <form method="POST" action="{{ route('admin.kelas-kuliah.peserta.store', $kelas) }}" class="flex items-center justify-between gap-3 border-b border-border px-4 py-2 last:border-b-0">
                            @csrf
                            <input type="hidden" name="mahasiswa_id" value="{{ $mahasiswa->mahasiswa_id }}">
                            <span class="min-w-0">
                                <span class="block font-medium">{{ $mahasiswa->nama }}</span>
                                <span class="text-xs text-text-muted">NPM {{ $mahasiswa->nim }} · Angkatan {{ $mahasiswa->angkatan }}</span>
                            </span>
                            <x-tombol varian="outline" type="submit" class="shrink-0">
                                <x-heroicon-o-plus class="size-5" />
                                Tambah
                            </x-tombol>
                        </form>
                    @empty
                        <p class="px-4 py-3 text-sm text-text-muted">Tidak ada mahasiswa yang cocok dan belum terdaftar.</p>
                    @endforelse
                </div>
            @endif

            <h3 class="mt-6 mb-3 font-semibold">Daftar Peserta ({{ $daftarPeserta->count() }})</h3>
            @if ($daftarPeserta->isEmpty())
                <x-kosong pesan="Belum ada peserta di kelas ini." />
            @else
                <x-tabel :kolom="['NPM', 'Nama Mahasiswa', 'Angkatan', 'Aksi']">
                    @foreach ($daftarPeserta as $peserta)
                        <x-tabel.baris>
                            <x-tabel.sel label="NPM" class="whitespace-nowrap">{{ $peserta->mahasiswa->nim }}</x-tabel.sel>
                            <x-tabel.sel label="Nama Mahasiswa" class="font-medium">{{ $peserta->mahasiswa->nama }}</x-tabel.sel>
                            <x-tabel.sel label="Angkatan">{{ $peserta->mahasiswa->angkatan }}</x-tabel.sel>
                            <x-tabel.aksi>
                                <x-tombol-hapus :aksi="route('admin.kelas-kuliah.peserta.destroy', [$kelas, $peserta])" :nama="$peserta->mahasiswa->nama.' dari kelas ini'" />
                            </x-tabel.aksi>
                        </x-tabel.baris>
                    @endforeach
                </x-tabel>
            @endif
        </x-kartu>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
            <x-tombol varian="outline" :href="route('admin.kelas-kuliah.index', ['periode' => $kelas->periode_akademik_id])">Kembali ke Daftar Kelas</x-tombol>
            <x-tombol varian="outline" :href="route('admin.kelas-kuliah.edit', $kelas)">
                <x-heroicon-o-pencil-square class="size-5" />
                Ubah Kelas
            </x-tombol>
        </div>
    </div>
</x-layouts.app>
