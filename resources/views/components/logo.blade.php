@props(['ukuran' => 'size-12'])

{{-- Logo disediakan di public/images/logo-unkhair.png; selama belum ada, tampil teks "UNKHAIR" (bagian 7.1 spesifikasi). --}}
@if (file_exists(public_path('images/logo-unkhair.png')))
    <img src="{{ asset('images/logo-unkhair.png') }}" alt="Logo Universitas Khairun" {{ $attributes->class([$ukuran, 'shrink-0 object-contain']) }}>
@else
    <span {{ $attributes->class([$ukuran, 'flex shrink-0 items-center justify-center rounded-lg bg-primary-900 text-[0.625rem] font-bold tracking-tight text-accent']) }}>UNKHAIR</span>
@endif
