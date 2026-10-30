@props(['judul' => null])

<section {{ $attributes->class('rounded-xl border border-border bg-surface p-5 shadow-xs sm:p-6') }}>
    @if ($judul)
        <h2 class="mb-4 text-lg font-semibold">{{ $judul }}</h2>
    @endif

    {{ $slot }}
</section>
