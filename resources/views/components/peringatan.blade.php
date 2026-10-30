{{-- Kotak peringatan kuning (warning) untuk bobot yang tidak sesuai target; tidak memblokir (bagian 6). --}}
<div {{ $attributes->class('flex items-start gap-3 rounded-lg border border-warning/20 bg-warning-bg px-4 py-3 text-sm text-warning') }} role="status">
    <x-heroicon-o-exclamation-triangle class="size-5 shrink-0" />
    <div class="min-w-0 flex-1">{{ $slot }}</div>
</div>
