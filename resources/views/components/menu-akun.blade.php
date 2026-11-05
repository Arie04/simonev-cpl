{{-- Menu akun di top bar. $peranAktif dibagikan oleh middleware CekPeran. --}}
@php($user = auth()->user())

<div class="relative" x-data="{ terbuka: false }" @click.outside="terbuka = false" @keydown.escape="terbuka = false">
    <button
        type="button"
        @click="terbuka = ! terbuka"
        :aria-expanded="terbuka"
        aria-haspopup="true"
        aria-label="Menu akun"
        class="flex min-h-11 items-center gap-2 rounded-lg px-1.5 hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-white sm:px-2"
    >
        <span class="flex size-9 items-center justify-center rounded-full bg-accent text-sm font-bold text-on-accent">{{ $user->inisial() }}</span>
        {{-- Di mobile nama peran disembunyikan, tinggal avatar (bagian 7.4 spesifikasi) --}}
        <span class="hidden text-sm font-medium md:inline">{{ $peranAktif->nama_role }}</span>
        <x-heroicon-o-chevron-down class="size-4" />
    </button>

    <div
        x-show="terbuka"
        x-cloak
        x-transition.origin.top.right
        class="absolute right-0 mt-2 w-64 overflow-hidden rounded-xl border border-border bg-surface py-2 text-sm text-text shadow-lg"
    >
        <div class="mb-1 border-b border-border px-4 pb-3">
            <p class="truncate font-semibold">{{ $user->nama }}</p>
            <p class="text-xs text-text-muted">{{ $peranAktif->nama_role }}</p>
        </div>

        {{-- Ubah Password tidak ada di menu ini; tombolnya ada di halaman Profil (KD-81). --}}
        <a href="{{ route('profil') }}" class="flex min-h-11 items-center gap-3 px-4 hover:bg-bg">
            <x-heroicon-o-user-circle class="size-5 text-text-muted" />
            Profil
        </a>
        @if ($user->roles->count() > 1)
            <a href="{{ route('peran-aktif.create') }}" class="flex min-h-11 items-center gap-3 px-4 hover:bg-bg">
                <x-heroicon-o-arrows-right-left class="size-5 text-text-muted" />
                Ganti Peran
            </a>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-border pt-1">
            @csrf
            <button type="submit" class="flex min-h-11 w-full items-center gap-3 px-4 text-left hover:bg-bg">
                <x-heroicon-o-arrow-right-start-on-rectangle class="size-5 text-text-muted" />
                Keluar
            </button>
        </form>
    </div>
</div>
