@props(['judul'])

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Format judul tab: "<Nama halaman> · SIMONEV-CPL" (bagian 7.1 spesifikasi) --}}
    <title>{{ $judul }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
