@props(['judul'])

{{-- Layout tanpa sidebar untuk login, pilih peran, dan halaman error. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<x-head :judul="$judul" />
<body class="flex min-h-screen flex-col">
    <x-top-bar :tamu="true" />

    <main class="relative flex flex-1 items-center justify-center overflow-hidden px-4 py-10">
        {{-- Ornamen lingkaran tipis (bagian 7.6 spesifikasi) --}}
        <div aria-hidden="true" class="pointer-events-none absolute -top-28 -left-28 size-80 rounded-full border-[48px] border-primary-50"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -right-36 -bottom-36 size-[28rem] rounded-full border-[56px] border-primary-50"></div>

        <div class="relative w-full max-w-[400px]">
            {{ $slot }}
        </div>
    </main>
</body>
</html>
