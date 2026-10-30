<x-layouts.app judul="Rencana Asesmen">
    <p class="-mt-3 mb-6 text-text-muted">Pilih kelas yang Anda ampu untuk mengelola rencana asesmennya.</p>

    @forelse ($daftarKelas as $namaPeriode => $kelasPeriode)
        <x-kartu :judul="'Periode '.$namaPeriode" class="mb-6">
            <x-tabel :kolom="['Mata Kuliah', 'Kelas', 'Peserta', 'Asesmen', 'Aksi']">
                @foreach ($kelasPeriode as $kelas)
                    <x-tabel.baris>
                        <x-tabel.sel label="Mata Kuliah" :panjang="true">
                            <span class="font-medium">{{ $kelas->mataKuliah->kode_mata_kuliah }}</span> {{ $kelas->mataKuliah->nama_mata_kuliah }}
                            <span class="block text-xs text-text-muted">{{ $kelas->mataKuliah->kurikulum->nama_kurikulum }}</span>
                        </x-tabel.sel>
                        <x-tabel.sel label="Kelas">{{ $kelas->nama_kelas }}</x-tabel.sel>
                        <x-tabel.sel label="Peserta">{{ $kelas->peserta_kelas_count }}</x-tabel.sel>
                        <x-tabel.sel label="Asesmen">{{ $kelas->rencana_asesmen_count }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol varian="outline" :href="route('dosen.kelas.rencana-asesmen.index', $kelas)">Kelola</x-tombol>
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>
        </x-kartu>
    @empty
        <x-kartu>
            <x-kosong pesan="Anda belum mengampu kelas. Admin menentukan dosen pengampu di menu Kelas Kuliah." />
        </x-kartu>
    @endforelse
</x-layouts.app>
