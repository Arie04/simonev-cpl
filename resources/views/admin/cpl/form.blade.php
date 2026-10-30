<x-layouts.app :judul="$cpl->exists ? 'Ubah CPL' : 'Tambah CPL'">
    <x-kartu class="max-w-3xl">
        <p class="mb-5 text-sm text-text-muted">
            Kurikulum: <span class="font-medium text-text">{{ $kurikulum->nama_kurikulum }}</span>
        </p>
        @error('kurikulum_id')
            <p class="mb-5 text-sm text-danger">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ $cpl->exists ? route('admin.cpl.update', $cpl) : route('admin.cpl.store') }}" novalidate>
            @csrf
            @if ($cpl->exists)
                @method('PUT')
            @else
                <input type="hidden" name="kurikulum_id" value="{{ $kurikulum->kurikulum_id }}">
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <x-input label="Kode CPL" name="kode_cpl" :value="$cpl->kode_cpl" keterangan="Unik dalam satu kurikulum, mis. CPL01." />
                <x-input label="Kategori CPL" name="kategori_cpl" :value="$cpl->kategori_cpl" :saran="['Sikap', 'Pengetahuan', 'Keterampilan Umum', 'Keterampilan Khusus']" />
                <div class="md:col-span-2">
                    <x-textarea label="Deskripsi CPL" name="deskripsi_cpl" :value="$cpl->deskripsi_cpl" rows="4" />
                </div>
            </div>

            <x-form-aksi :batal="route('admin.cpl.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
