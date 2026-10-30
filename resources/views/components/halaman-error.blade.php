@props(['kode', 'judul', 'pesan'])

{{-- Dipakai halaman error 403, 404, 419, dan 500 (bagian 7.6 spesifikasi). Sengaja tidak
     membaca session atau database, karena halaman 500 bisa muncul saat keduanya bermasalah. --}}
<x-layouts.guest :judul="$judul">
    <x-kartu class="px-6 py-10 text-center sm:px-8">
        <p class="text-5xl font-bold text-primary-700">{{ $kode }}</p>
        <h1 class="mt-3 text-xl font-semibold">{{ $judul }}</h1>
        <p class="mt-2 text-text-muted">{{ $pesan }}</p>
        <x-tombol :href="url('/')" class="mt-6">
            <x-heroicon-o-home class="size-5" />
            Kembali ke Beranda
        </x-tombol>
    </x-kartu>
</x-layouts.guest>
