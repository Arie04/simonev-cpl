@props(['varian' => 'netral', 'ikon' => null])

@php
    // Warna status sama di semua tempat: hijau Tercapai/Lulus, merah Tidak Tercapai/Belum Lulus,
    // abu belum bisa dinilai (bagian 7.2). Biru hanya untuk identitas, mis. nama peran.
    $kelasVarian = match ($varian) {
        'sukses' => 'bg-success-bg text-success',
        'bahaya' => 'bg-danger-bg text-danger',
        'peringatan' => 'bg-warning-bg text-warning',
        'info' => 'bg-primary-50 text-primary-900',
        default => 'bg-neutral-bg text-neutral',
    };
@endphp

<span {{ $attributes->class('inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap '.$kelasVarian) }}>
    @if ($ikon)
        <x-dynamic-component :component="$ikon" class="size-4" />
    @endif
    {{ $slot }}
</span>
