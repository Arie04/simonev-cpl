@props(['tamu' => false])

<header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-2 bg-primary-900 px-2 text-white sm:px-4">
    <div class="flex min-w-0 items-center gap-1 sm:gap-2">
        @unless ($tamu)
            <button
                type="button"
                @click="alihkanSidebar()"
                class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg hover:bg-primary-800 focus-visible:outline-2 focus-visible:outline-white"
                aria-label="Buka atau tutup menu"
            >
                <x-heroicon-o-bars-3 class="size-6" />
            </button>
        @endunless

        <a href="{{ url('/') }}" class="flex min-w-0 items-baseline gap-3 rounded px-2 py-1 focus-visible:outline-2 focus-visible:outline-white">
            <span class="text-lg font-bold tracking-wide">SIMONEV-CPL</span>
            {{-- Judul lengkap hanya tampil di desktop (bagian 7.1 dan 7.4 spesifikasi) --}}
            <span class="hidden truncate text-sm font-medium text-primary-100 lg:inline">Sistem Monitoring &amp; Evaluasi Capaian Pembelajaran</span>
        </a>
    </div>

    @unless ($tamu)
        <x-menu-akun />
    @endunless
</header>
