<x-layouts.app :judul="$cpmk->exists ? 'Ubah CPMK' : 'Tambah CPMK'">
    <x-kartu class="max-w-3xl">
        <p class="mb-5 text-sm text-text-muted">
            Kurikulum: <span class="font-medium text-text">{{ $kurikulum->nama_kurikulum }}</span>
        </p>
        @error('kurikulum_id')
            <p class="mb-5 text-sm text-danger">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ $cpmk->exists ? route('admin.cpmk.update', $cpmk) : route('admin.cpmk.store') }}" novalidate>
            @csrf
            @if ($cpmk->exists)
                @method('PUT')
            @else
                <input type="hidden" name="kurikulum_id" value="{{ $kurikulum->kurikulum_id }}">
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-select label="CPL" name="cpl_id" :pilihan="$daftarCpl" :value="$cpmk->cpl_id" kosong="Pilih CPL" keterangan="Setiap CPMK milik tepat satu CPL." />
                </div>
                <div class="md:col-span-2">
                    <x-input label="Kode CPMK" name="kode_cpmk" :value="$cpmk->kode_cpmk" keterangan="Unik dalam satu kurikulum, mis. CPMK011." />
                </div>
                <div class="md:col-span-2">
                    <x-textarea label="Deskripsi CPMK" name="deskripsi_cpmk" :value="$cpmk->deskripsi_cpmk" rows="4" />
                </div>
            </div>

            <x-form-aksi :batal="route('admin.cpmk.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
