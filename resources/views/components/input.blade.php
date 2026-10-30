@props([
    'label',
    'name',
    'type' => 'text',
    'ikon' => null,
    'value' => null,
    'keterangan' => null,
    'saran' => [],
])

@php
    $id = $attributes->get('id', $name);
    $adaError = $errors->has($name);
    $jenisPassword = $type === 'password';
@endphp

{{-- Label di atas input, tinggi minimal 44px, pesan error merah di bawah field (bagian 7.3 spesifikasi). --}}
<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium">{{ $label }}</label>

    <div class="relative" @if ($jenisPassword) x-data="{ tampil: false }" @endif>
        @if ($ikon)
            <x-dynamic-component :component="$ikon" class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-text-muted" />
        @endif

        <input
            id="{{ $id }}"
            name="{{ $name }}"
            @if ($jenisPassword)
                type="password"
                :type="tampil ? 'text' : 'password'"
            @else
                type="{{ $type }}"
                value="{{ old($name, $value) }}"
            @endif
            @if ($saran) list="{{ $id }}-saran" @endif
            @if ($adaError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{-- text-base (16px) di mobile agar Safari iOS tidak memperbesar halaman (bagian 7.4) --}}
            {{ $attributes->except('id')->class([
                'block min-h-11 w-full rounded-lg border bg-surface text-base placeholder:text-text-muted focus:border-primary-700 focus:ring-2 focus:ring-primary-700/30 focus:outline-none sm:text-sm',
                $ikon ? 'pl-10' : 'pl-3',
                $jenisPassword ? 'pr-11' : 'pr-3',
                $adaError ? 'border-danger' : 'border-border',
            ]) }}
        >

        {{-- Saran isian (mis. kategori CPL); pengguna tetap boleh mengetik nilai lain (KD-42). --}}
        @if ($saran)
            <datalist id="{{ $id }}-saran">
                @foreach ($saran as $pilihan)
                    <option value="{{ $pilihan }}"></option>
                @endforeach
            </datalist>
        @endif

        @if ($jenisPassword)
            <button
                type="button"
                @click="tampil = ! tampil"
                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-text-muted hover:text-text focus-visible:outline-2 focus-visible:outline-primary-700"
                aria-label="Tampilkan password"
                :aria-label="tampil ? 'Sembunyikan password' : 'Tampilkan password'"
            >
                <x-heroicon-o-eye x-show="! tampil" class="size-5" />
                <x-heroicon-o-eye-slash x-show="tampil" x-cloak class="size-5" />
            </button>
        @endif
    </div>

    @if ($keterangan && ! $adaError)
        <p class="mt-1.5 text-xs text-text-muted">{{ $keterangan }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>
