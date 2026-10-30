@props(['pesan', 'tambah' => null, 'labelTambah' => 'Tambah Data'])

{{-- Empty state: satu kalimat dan tombol aksi (bagian 7.3 spesifikasi). --}}
<div {{ $attributes->class('flex flex-col items-center gap-4 px-4 py-10 text-center') }}>
    <x-heroicon-o-inbox class="size-10 text-text-muted" />
    <p class="text-text-muted">{{ $pesan }}</p>
    @if ($tambah)
        <x-tombol :href="$tambah">
            <x-heroicon-o-plus class="size-5" />
            {{ $labelTambah }}
        </x-tombol>
    @endif
    {{ $slot }}
</div>
