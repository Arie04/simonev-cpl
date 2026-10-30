<x-layouts.app :judul="$profesi->exists ? 'Ubah Profesi' : 'Tambah Profesi'">
    <x-kartu class="max-w-3xl">
        <p class="mb-5 text-sm text-text-muted">
            Kurikulum: <span class="font-medium text-text">{{ $kurikulum->nama_kurikulum }}</span>
        </p>
        @error('kurikulum_id')
            <p class="mb-5 text-sm text-danger">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ $profesi->exists ? route('admin.profesi.update', $profesi) : route('admin.profesi.store') }}" novalidate>
            @csrf
            @if ($profesi->exists)
                @method('PUT')
            @else
                <input type="hidden" name="kurikulum_id" value="{{ $kurikulum->kurikulum_id }}">
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input label="Nama Profesi" name="nama_profesi" :value="$profesi->nama_profesi" keterangan="Profil lulusan yang terkait diatur di form Profil Lulusan." />
                </div>
            </div>

            <x-form-aksi :batal="route('admin.profesi.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
