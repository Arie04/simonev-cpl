{{-- Halaman sambutan sementara untuk halaman utama tiap peran (KD-21). --}}
<x-layouts.app :judul="$judul">
    <x-kartu>
        <div class="flex items-start gap-4">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700">
                <x-heroicon-o-sparkles class="size-6" />
            </span>
            <div>
                <p class="text-lg font-semibold">Selamat datang, {{ auth()->user()->nama }}.</p>
                <p class="mt-1 text-text-muted">
                    Anda masuk sebagai <span class="font-semibold text-text">{{ $peranAktif->nama_role }}</span>.
                    Isi halaman ini sedang dikembangkan (iterasi {{ $iterasi }}).
                </p>
            </div>
        </div>
    </x-kartu>
</x-layouts.app>
