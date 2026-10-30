@if ($daftarEvaluasi->isEmpty())
    <x-kosong pesan="Belum ada data evaluasi kurikulum." />
@else
    <x-tabel :kolom="['Tahap Evaluasi', 'Kinerja Mutu', 'Standar Kinerja Mutu', 'Kesenjangan', 'Tindak Lanjut']">
        @foreach ($daftarEvaluasi as $evaluasi)
            <x-tabel.baris>
                <x-tabel.sel label="Tahap Evaluasi" class="font-medium">{{ $evaluasi->tahap_evaluasi }}</x-tabel.sel>
                <x-tabel.sel label="Kinerja Mutu" :panjang="true">{{ $evaluasi->kinerja_mutu }}</x-tabel.sel>
                <x-tabel.sel label="Standar Kinerja Mutu" :panjang="true">{{ $evaluasi->standar_kinerja_mutu }}</x-tabel.sel>
                <x-tabel.sel label="Kesenjangan" :panjang="true">{{ $evaluasi->kesenjangan ?? '–' }}</x-tabel.sel>
                <x-tabel.sel label="Tindak Lanjut" :panjang="true">{{ $evaluasi->tindak_lanjut ?? '–' }}</x-tabel.sel>
            </x-tabel.baris>
        @endforeach
    </x-tabel>
@endif
