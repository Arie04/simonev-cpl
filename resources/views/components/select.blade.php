@props([
    'label',
    'name',
    'pilihan' => [],
    'value' => null,
    'kosong' => null,
    'keterangan' => null,
])

@php
    $id = $attributes->get('id', $name);
    $adaError = $errors->has($name);
    $terpilih = (string) old($name, $value);
@endphp

{{-- Select dengan ikon chevron. $pilihan berisi pasangan nilai => label; $kosong adalah teks
     pilihan pertama tanpa nilai, mis. "Pilih CPL" atau "Semua periode". --}}
<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium">{{ $label }}</label>

    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            @if ($adaError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except('id')->class([
                'block min-h-11 w-full appearance-none rounded-lg border bg-surface py-2 pr-10 pl-3 text-base focus:border-primary-700 focus:ring-2 focus:ring-primary-700/30 focus:outline-none sm:text-sm',
                $adaError ? 'border-danger' : 'border-border',
            ]) }}
        >
            @if ($kosong !== null)
                <option value="">{{ $kosong }}</option>
            @endif
            @foreach ($pilihan as $nilai => $teks)
                <option value="{{ $nilai }}" @selected($terpilih === (string) $nilai)>{{ $teks }}</option>
            @endforeach
        </select>
        <x-heroicon-o-chevron-down class="pointer-events-none absolute top-1/2 right-3 size-5 -translate-y-1/2 text-text-muted" />
    </div>

    @if ($keterangan && ! $adaError)
        <p class="mt-1.5 text-xs text-text-muted">{{ $keterangan }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
