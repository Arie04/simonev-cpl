<x-layouts.app judul="Ubah Rincian Penilaian">
    <x-kartu class="max-w-3xl">
        <p class="mb-5 text-sm text-text-muted">
            Sub-CPMK: <span class="font-medium text-text">{{ $subCpmk->kode_sub_cpmk }}</span> – {{ $subCpmk->deskripsi_sub_cpmk }}
        </p>

        <form method="POST" action="{{ route('admin.sub-cpmk.penilaian.update', [$subCpmk, $penilaian]) }}" novalidate>
            @csrf
            @method('PUT')

            <div class="grid gap-5 md:grid-cols-2">
                <x-select label="Metode Penilaian" name="metode_penilaian_id" :pilihan="$daftarMetode" :value="$penilaian->metode_penilaian_id" kosong="Pilih metode" />
                <x-input label="Teknik Penilaian" name="teknik_penilaian" :value="$penilaian->teknik_penilaian" />
                <x-input label="Instrumen" name="instrumen" :value="$penilaian->instrumen" />
                <x-textarea label="Kriteria" name="kriteria" rows="2" :value="$penilaian->kriteria" />
            </div>

            <x-form-aksi :batal="route('admin.sub-cpmk.show', $subCpmk)" />
        </form>
    </x-kartu>
</x-layouts.app>
