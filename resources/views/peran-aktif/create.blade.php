{{-- Pilih peran aktif setelah login, juga dipakai untuk "Ganti Peran" (bagian 3 spesifikasi). --}}
@php
    $ikonPeran = [
        'ADMIN' => 'heroicon-o-cog-6-tooth',
        'KOPRODI' => 'heroicon-o-presentation-chart-bar',
        'DOSEN' => 'heroicon-o-academic-cap',
        'MAHASISWA' => 'heroicon-o-user',
    ];
@endphp

<x-layouts.guest judul="Pilih Peran">
    <x-kartu class="px-6 py-8 sm:px-8">
        <div class="mb-6 text-center">
            <h1 class="text-xl font-semibold">Pilih Peran</h1>
            <p class="mt-2 text-sm text-text-muted">
                Halo, {{ auth()->user()->nama }}. Akun Anda memiliki lebih dari satu peran.
                Pilih peran yang ingin dipakai.
            </p>
        </div>

        @error('role_id')
            <p class="mb-4 rounded-lg bg-danger-bg p-3 text-sm text-danger">{{ $message }}</p>
        @enderror

        <div class="space-y-3">
            @foreach ($daftarPeran as $peran)
                <form method="POST" action="{{ route('peran-aktif.store') }}">
                    @csrf
                    <input type="hidden" name="role_id" value="{{ $peran->role_id }}">

                    <button
                        type="submit"
                        class="flex min-h-14 w-full items-center gap-3 rounded-xl border border-border px-4 py-3 text-left hover:border-primary-700 hover:bg-primary-50 focus-visible:outline-2 focus-visible:outline-primary-700"
                    >
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700">
                            <x-dynamic-component :component="$ikonPeran[$peran->kode_role]" class="size-5" />
                        </span>
                        <span class="flex-1">
                            <span class="block font-semibold">{{ $peran->nama_role }}</span>
                            @if ($peran->kode_role === $kodePeranAktif)
                                <span class="block text-xs text-text-muted">Sedang aktif</span>
                            @endif
                        </span>
                        <x-heroicon-o-chevron-right class="size-5 shrink-0 text-text-muted" />
                    </button>
                </form>
            @endforeach
        </div>

        {{-- min-h-11 dan px-3: target sentuh minimal 44×44 px di mobile (bagian 7.4). --}}
        <div class="mt-4 flex items-center justify-center gap-2 text-sm">
            @if ($kodePeranAktif)
                <a href="{{ route('beranda') }}" class="inline-flex min-h-11 items-center px-3 font-medium text-primary-700 hover:underline">Batal</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex min-h-11 items-center px-3 font-medium text-primary-700 hover:underline">Keluar</button>
            </form>
        </div>
    </x-kartu>
</x-layouts.guest>
