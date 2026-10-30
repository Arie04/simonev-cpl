<x-layouts.app judul="Mata Kuliah">
    <x-kartu>
        <p class="mb-4 text-sm text-text-muted">Mata kuliah dari kelas yang pernah atau sedang Anda ampu.</p>

        @if ($daftarMataKuliah->isEmpty())
            <x-kosong pesan="Anda belum mengampu mata kuliah." />
        @else
            <x-tabel :kolom="['Kode', 'Nama Mata Kuliah', 'Kurikulum', 'Semester', 'SKS', 'Kelas Diampu', 'Aksi']">
                @foreach ($daftarMataKuliah as $mataKuliah)
                    <x-tabel.baris>
                        <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">{{ $mataKuliah->kode_mata_kuliah }}</x-tabel.sel>
                        <x-tabel.sel label="Nama Mata Kuliah" :panjang="true">{{ $mataKuliah->nama_mata_kuliah }}</x-tabel.sel>
                        <x-tabel.sel label="Kurikulum">{{ $mataKuliah->kurikulum->nama_kurikulum }}</x-tabel.sel>
                        <x-tabel.sel label="Semester">{{ $mataKuliah->semester }}</x-tabel.sel>
                        <x-tabel.sel label="SKS">{{ $mataKuliah->jumlah_sks }}</x-tabel.sel>
                        <x-tabel.sel label="Kelas Diampu">{{ $mataKuliah->kelas_kuliah_count }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon ikon="heroicon-o-eye" :label="'Lihat CPL, CPMK, dan Sub-CPMK '.$mataKuliah->kode_mata_kuliah" :href="route('dosen.mata-kuliah.show', $mataKuliah)" />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>
        @endif
    </x-kartu>
</x-layouts.app>
