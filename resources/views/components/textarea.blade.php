@props([
    'label',
    'name',
    'value' => null,
    'rows' => 3,
    'keterangan' => null,
])

@php
    $id = $attributes->get('id', $name);
    $adaError = $errors->has($name);
@endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium">{{ $label }}</label>

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($adaError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->class([
            'block w-full rounded-lg border bg-surface px-3 py-2.5 text-base focus:border-primary-700 focus:ring-2 focus:ring-primary-700/30 focus:outline-none sm:text-sm',
            $adaError ? 'border-danger' : 'border-border',
        ]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($keterangan && ! $adaError)
        <p class="mt-1.5 text-xs text-text-muted">{{ $keterangan }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
