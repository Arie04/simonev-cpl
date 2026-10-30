{{-- Halaman login (bagian 7.6 butir 1, Gambar 3.7). --}}
<x-layouts.guest judul="Login">
    <x-kartu class="px-6 py-8 sm:px-8">
        <div class="mb-6 flex flex-col items-center text-center">
            <x-logo ukuran="size-16" />
            <p class="mt-3 text-xl font-bold text-primary-900">SIMONEV-CPL</p>
            <h1 class="mt-1 text-lg font-semibold">Masuk ke Sistem</h1>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4" novalidate>
            @csrf

            <x-input
                label="Email / NPM / NIDN"
                name="identitas"
                ikon="heroicon-o-user"
                autocomplete="username"
                autofocus
                required
            />

            <x-input
                label="Password"
                name="password"
                type="password"
                ikon="heroicon-o-lock-closed"
                autocomplete="current-password"
                required
            />

            <x-tombol type="submit" class="w-full">Login</x-tombol>
        </form>

        <p class="mt-6 text-center text-sm text-text-muted">Lupa password? Hubungi Admin program studi.</p>
    </x-kartu>
</x-layouts.guest>
