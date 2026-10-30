@props(['ikon', 'label', 'nilai', 'keterangan' => null])

{{-- Kartu statistik: ikon dalam lingkaran primary-100, label kecil, dan angka besar (bagian 7.3). --}}
<div {{ $attributes->class('flex flex-col gap-3 rounded-xl border border-border bg-surface p-4 shadow-xs sm:flex-row sm:items-center sm:gap-4 sm:p-5') }}>
    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700">
        <x-dynamic-component :component="$ikon" class="size-6" />
    </span>
    <div class="min-w-0">
        <p class="text-sm text-text-muted">{{ $label }}</p>
        <p class="text-2xl font-bold sm:text-[1.75rem]">{{ $nilai }}</p>
        @if ($keterangan)
            <p class="text-xs break-words text-text-muted">{{ $keterangan }}</p>
        @endif
    </div>
</div>
