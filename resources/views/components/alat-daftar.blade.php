@props([
    'tambah' => null,
    'labelTambah' => 'Tambah Data',
    'daftarKurikulum' => null,
    'kurikulum' => null,
])

{{-- Bagian atas halaman daftar (bagian 7.6.4): dropdown Kurikulum untuk data yang bergantung pada
     kurikulum, kotak "Cari data...", dan tombol "Tambah Data". Semuanya satu form GET, jadi kata
     pencarian tetap terbawa saat kurikulum diganti. --}}
<form method="GET" class="mb-4 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
    <div class="md:w-72">
        @if ($daftarKurikulum !== null)
            <x-select
                name="kurikulum"
                label="Kurikulum"
                :pilihan="$daftarKurikulum->pluck('nama_kurikulum', 'kurikulum_id')"
                :value="$kurikulum?->kurikulum_id"
                x-on:change="$el.form.submit()"
            />
        @endif
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex gap-2">
            <div class="relative flex-1 sm:w-64">
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-text-muted" />
                <label for="cari" class="sr-only">Cari data</label>
                <input
                    id="cari"
                    type="search"
                    name="cari"
                    value="{{ request('cari') }}"
                    placeholder="Cari data..."
                    class="block min-h-11 w-full rounded-lg border border-border bg-surface pr-3 pl-10 text-base focus:border-primary-700 focus:ring-2 focus:ring-primary-700/30 focus:outline-none sm:text-sm"
                >
            </div>
            <x-tombol varian="outline" type="submit">Cari</x-tombol>
        </div>

        @if ($tambah)
            <x-tombol :href="$tambah">
                <x-heroicon-o-plus class="size-5" />
                {{ $labelTambah }}
            </x-tombol>
        @endif
    </div>

    @if (request('per_halaman'))
        <input type="hidden" name="per_halaman" value="{{ request('per_halaman') }}">
    @endif
</form>
