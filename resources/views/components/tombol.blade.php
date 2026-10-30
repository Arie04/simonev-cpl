@props(['varian' => 'utama', 'href' => null, 'type' => 'button'])

@php
    // Satu tombol utama (biru terisi) per halaman; tombol lain outline (bagian 7.3 spesifikasi).
    $kelasVarian = match ($varian) {
        'utama' => 'bg-primary-700 text-white hover:bg-primary-800',
        'outline' => 'border border-border bg-surface text-text hover:bg-bg',
        'bahaya' => 'bg-danger-chart text-white hover:bg-danger',
    };
    $kelas = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold transition-colors '
        .'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 '
        .'disabled:cursor-not-allowed disabled:opacity-60 '.$kelasVarian;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($kelas) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($kelas) }}>{{ $slot }}</button>
@endif
