{{-- Menu sidebar mengikuti peran aktif ($peranAktif dibagikan middleware CekPeran). --}}
@php($daftarMenu = config('menu.'.$peranAktif->kode_role, []))

{{-- Latar gelap transparan di belakang drawer, hanya di layar < 1024px --}}
<div
    x-show="drawerMobile"
    x-cloak
    x-transition.opacity
    @click="tutupDrawer()"
    class="fixed inset-0 z-40 bg-text/50 lg:hidden"
></div>

{{-- Di layar < 1024px sidebar menjadi drawer dari kiri; di desktop selalu tampil kecuali disembunyikan lewat hamburger (bagian 7.4). --}}
<aside
    :data-terbuka="drawerMobile"
    :data-tersembunyi="! sidebarDesktop"
    class="fixed inset-y-0 left-0 z-50 flex w-64 shrink-0 -translate-x-full flex-col border-r border-border bg-surface transition-transform duration-200 data-[terbuka=true]:translate-x-0 lg:sticky lg:top-16 lg:z-auto lg:h-[calc(100dvh-4rem)] lg:translate-x-0 lg:data-[tersembunyi=true]:hidden"
    aria-label="Menu utama"
>
    <div class="flex items-center justify-between gap-2 border-b border-border px-4 py-4">
        <div class="flex min-w-0 items-center gap-3">
            <x-logo ukuran="size-12" />
            <div class="min-w-0 leading-tight">
                <p class="text-sm font-semibold">Universitas Khairun</p>
                <p class="text-xs text-text-muted">Program Studi Informatika</p>
            </div>
        </div>
        <button
            type="button"
            @click="tutupDrawer()"
            class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-text-muted hover:bg-bg lg:hidden"
            aria-label="Tutup menu"
        >
            <x-heroicon-o-x-mark class="size-6" />
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1">
            @foreach ($daftarMenu as $menu)
                @isset($menu['anak'])
                    {{-- Grup menu yang bisa dibuka-tutup; terbuka otomatis jika salah satu anaknya aktif. --}}
                    @php($grupAktif = collect($menu['anak'])->contains(fn ($anak) => request()->routeIs($anak['aktif'])))
                    <li x-data="{ terbuka: @js($grupAktif) }">
                        <button
                            type="button"
                            x-on:click="terbuka = ! terbuka"
                            x-bind:aria-expanded="terbuka"
                            class="flex min-h-11 w-full items-center gap-3 border-l-[3px] border-transparent px-4 text-left text-sm font-medium text-text-muted hover:bg-bg hover:text-text"
                        >
                            <x-dynamic-component :component="$menu['ikon']" class="size-5 shrink-0" />
                            <span class="flex-1">{{ $menu['label'] }}</span>
                            <x-heroicon-o-chevron-down class="size-4 shrink-0 transition-transform" x-bind:class="terbuka && 'rotate-180'" />
                        </button>
                        <ul x-show="terbuka" @unless ($grupAktif) x-cloak @endunless class="pb-1">
                            @foreach ($menu['anak'] as $anak)
                                @php($aktif = request()->routeIs($anak['aktif']))
                                <li>
                                    <a
                                        href="{{ route($anak['route']) }}"
                                        x-on:click="tutupDrawer()"
                                        @class([
                                            'flex min-h-11 items-center gap-3 border-l-[3px] py-2 pr-4 pl-12 text-sm',
                                            'border-accent bg-primary-50 font-medium text-primary-900' => $aktif,
                                            'border-transparent text-text-muted hover:bg-bg hover:text-text' => ! $aktif,
                                        ])
                                        @if ($aktif) aria-current="page" @endif
                                    >
                                        {{-- Sub-menu bertanda titik (bagian 7.1 spesifikasi) --}}
                                        <span class="size-1.5 shrink-0 rounded-full bg-current"></span>
                                        {{ $anak['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @else
                    @php($aktif = request()->routeIs($menu['aktif']))
                    <li>
                        <a
                            href="{{ route($menu['route']) }}"
                            x-on:click="tutupDrawer()"
                            @class([
                                'flex min-h-11 items-center gap-3 border-l-[3px] px-4 text-sm font-medium',
                                'border-accent bg-primary-50 text-primary-900' => $aktif,
                                'border-transparent text-text-muted hover:bg-bg hover:text-text' => ! $aktif,
                            ])
                            @if ($aktif) aria-current="page" @endif
                        >
                            <x-dynamic-component :component="$menu['ikon']" class="size-5 shrink-0" />
                            {{ $menu['label'] }}
                        </a>
                    </li>
                @endisset
            @endforeach
        </ul>
    </nav>
</aside>
