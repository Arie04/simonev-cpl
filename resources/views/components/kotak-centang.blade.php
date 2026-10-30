@props([
    'label',
    'name',
    'pilihan' => [],
    'terpilih' => [],
    'keterangan' => null,
    'kosong' => 'Belum ada pilihan.',
])

@php
    // Nilai lama dipakai lagi jika validasi gagal, supaya centang tidak hilang.
    $terpilih = array_map('strval', old($name, $terpilih));
    $adaError = $errors->has($name) || $errors->has($name.'.*');
@endphp

{{-- Daftar kotak centang untuk memilih lebih dari satu data, mis. peran akun atau profesi. --}}
<fieldset>
    <legend class="mb-1.5 text-sm font-medium">{{ $label }}</legend>

    @if (count($pilihan) === 0)
        <p class="text-sm text-text-muted">{{ $kosong }}</p>
    @else
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach ($pilihan as $nilai => $teks)
                <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-border px-3 py-2 text-sm has-checked:border-primary-700 has-checked:bg-primary-50">
                    <input
                        type="checkbox"
                        name="{{ $name }}[]"
                        value="{{ $nilai }}"
                        @checked(in_array((string) $nilai, $terpilih, true))
                        class="size-5 shrink-0 accent-primary-700"
                    >
                    <span>{{ $teks }}</span>
                </label>
            @endforeach
        </div>
    @endif

    @if ($keterangan && ! $adaError)
        <p class="mt-1.5 text-xs text-text-muted">{{ $keterangan }}</p>
    @endif

    @if ($adaError)
        <p class="mt-1.5 text-sm text-danger">{{ $errors->first($name) ?: $errors->first($name.'.*') }}</p>
    @endif
</fieldset>
