@props(['judul'])

{{-- Layout halaman setelah login: top bar, sidebar, dan area konten (bagian 7.1 spesifikasi). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<x-head :judul="$judul" />
<body x-data="tataLetak" @keydown.escape.window="tutupDrawer()">
    <x-top-bar />

    <div class="flex">
        <x-sidebar />

        <main class="min-w-0 flex-1 px-4 py-6 md:px-6 lg:px-8">
            <h1 class="mb-6 text-2xl font-semibold">{{ $judul }}</h1>

            {{ $slot }}
        </main>
    </div>

    <x-toast />
    <x-modal-hapus />
</body>
</html>
