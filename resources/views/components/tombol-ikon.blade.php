@props(['ikon', 'label', 'href' => null, 'varian' => 'biasa', 'type' => 'button'])

@php
    // Tombol aksi kecil bergaris di tabel; tombol hapus menjadi merah saat hover (bagian 7.3).
    // Ukurannya 44 × 44 px agar mudah disentuh (bagian 7.4).
    $kelas = 'inline-flex size-11 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text-muted transition-colors '
        .'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 '
        .($varian === 'bahaya'
            ? 'hover:border-danger hover:bg-danger-bg hover:text-danger'
            : 'hover:bg-bg hover:text-text');
@endphp

@if ($href)
    <a href="{{ $href }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($kelas) }}>
        <x-dynamic-component :component="$ikon" class="size-5" />
    </a>
@else
    <button type="{{ $type }}" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($kelas) }}>
        <x-dynamic-component :component="$ikon" class="size-5" />
    </button>
@endif
