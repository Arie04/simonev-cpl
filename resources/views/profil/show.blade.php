{{-- Profil pengguna (bagian 7.6 butir 13). --}}
<x-layouts.app judul="Profil">
    <x-kartu class="max-w-2xl">
        <div class="flex items-center gap-4 border-b border-border pb-5">
            <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-accent text-lg font-bold text-on-accent">
                {{ $user->inisial() }}
            </span>
            <div class="min-w-0">
                <p class="text-lg font-semibold break-words">{{ $user->nama }}</p>
                <p class="text-sm text-text-muted">{{ $peranAktif->nama_role }}</p>
            </div>
        </div>

        <dl class="mt-5 grid gap-x-6 gap-y-1 sm:grid-cols-[10rem_1fr] sm:gap-y-4">
            <dt class="text-sm text-text-muted">Nama</dt>
            <dd class="mb-3 sm:mb-0">{{ $user->nama }}</dd>

            <dt class="text-sm text-text-muted">Email</dt>
            <dd class="mb-3 break-all sm:mb-0">{{ $user->email ?? '–' }}</dd>

            @if ($user->mahasiswa)
                <dt class="text-sm text-text-muted">NPM</dt>
                <dd class="mb-3 sm:mb-0">{{ $user->mahasiswa->nim }}</dd>
            @endif

            @if ($user->dosen)
                <dt class="text-sm text-text-muted">NIDN</dt>
                <dd class="mb-3 sm:mb-0">{{ $user->dosen->nidn }}</dd>
            @endif

            <dt class="text-sm text-text-muted">Peran</dt>
            <dd>{{ $user->roles->pluck('nama_role')->sort()->implode(', ') }}</dd>
        </dl>

        <div class="mt-6">
            <x-tombol varian="outline" :href="route('password.edit')">
                <x-heroicon-o-key class="size-5" />
                Ubah Password
            </x-tombol>
        </div>
    </x-kartu>
</x-layouts.app>
