<x-layouts.app :judul="$evaluasiKurikulum->exists ? 'Ubah Evaluasi Kurikulum' : 'Tambah Evaluasi Kurikulum'">
    <x-kartu class="max-w-3xl">
        <p class="mb-5 text-sm text-text-muted">
            Kurikulum: <span class="font-medium text-text">{{ $kurikulum->nama_kurikulum }}</span>
        </p>
        @error('kurikulum_id')
            <p class="mb-5 text-sm text-danger">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ $evaluasiKurikulum->exists ? route('admin.evaluasi-kurikulum.update', $evaluasiKurikulum) : route('admin.evaluasi-kurikulum.store') }}" novalidate>
            @csrf
            @if ($evaluasiKurikulum->exists)
                @method('PUT')
            @else
                <input type="hidden" name="kurikulum_id" value="{{ $kurikulum->kurikulum_id }}">
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input label="Tahap Evaluasi" name="tahap_evaluasi" :value="$evaluasiKurikulum->tahap_evaluasi" keterangan="Mengikuti format Tabel B APTIKOM, mis. Evaluasi kurikulum 2021." />
                </div>
                <x-textarea label="Kinerja Mutu" name="kinerja_mutu" :value="$evaluasiKurikulum->kinerja_mutu" />
                <x-textarea label="Standar Kinerja Mutu" name="standar_kinerja_mutu" :value="$evaluasiKurikulum->standar_kinerja_mutu" />
                <x-textarea label="Kesenjangan" name="kesenjangan" :value="$evaluasiKurikulum->kesenjangan" keterangan="Boleh kosong." />
                <x-textarea label="Tindak Lanjut" name="tindak_lanjut" :value="$evaluasiKurikulum->tindak_lanjut" keterangan="Boleh kosong." />
            </div>

            <x-form-aksi :batal="route('admin.evaluasi-kurikulum.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
