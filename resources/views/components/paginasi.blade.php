@props(['data'])

@php
    // Nomor halaman di sekitar halaman aktif, ditambah halaman pertama dan terakhir.
    $halamanAktif = $data->currentPage();
    $halamanTerakhir = $data->lastPage();
    $daftarHalaman = collect([1, $halamanTerakhir])
        ->merge(range(max(1, $halamanAktif - 2), min($halamanTerakhir, $halamanAktif + 2)))
        ->unique()
        ->sort()
        ->values();
    $kelasTautan = 'inline-flex size-10 items-center justify-center rounded-lg border border-border bg-surface hover:bg-bg';
@endphp

{{-- Paginasi bagian 7.3 dan 7.4: di mobile cukup tombol Sebelumnya/Berikutnya dan "Hal 1 dari 5". --}}
@if ($data->total() > 0)
    <div class="mt-4 flex flex-col gap-3 text-sm md:flex-row md:items-center md:justify-between">
        <p class="text-text-muted">
            Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }} dari {{ $data->total() }} data
        </p>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            @if ($data->hasPages())
                <nav class="hidden items-center gap-1 md:flex" aria-label="Halaman">
                    @foreach ($daftarHalaman as $nomor)
                        @if (! $loop->first && $nomor - $daftarHalaman[$loop->index - 1] > 1)
                            <span class="px-1 text-text-muted">…</span>
                        @endif
                        @if ($nomor === $halamanAktif)
                            <span class="inline-flex size-10 items-center justify-center rounded-lg bg-primary-700 font-semibold text-white" aria-current="page">{{ $nomor }}</span>
                        @else
                            <a href="{{ $data->url($nomor) }}" class="{{ $kelasTautan }}" aria-label="Halaman {{ $nomor }}">{{ $nomor }}</a>
                        @endif
                    @endforeach
                </nav>

                <nav class="flex items-center justify-between gap-2 md:hidden" aria-label="Halaman">
                    @if ($data->onFirstPage())
                        <x-tombol varian="outline" disabled>Sebelumnya</x-tombol>
                    @else
                        <x-tombol varian="outline" :href="$data->previousPageUrl()">Sebelumnya</x-tombol>
                    @endif
                    <span class="text-text-muted">Hal {{ $halamanAktif }} dari {{ $halamanTerakhir }}</span>
                    @if ($data->hasMorePages())
                        <x-tombol varian="outline" :href="$data->nextPageUrl()">Berikutnya</x-tombol>
                    @else
                        <x-tombol varian="outline" disabled>Berikutnya</x-tombol>
                    @endif
                </nav>
            @endif

            {{-- Pilihan jumlah baris per halaman; filter dan pencarian lain tetap dibawa. --}}
            <form method="GET" class="flex items-center gap-2 md:ml-2">
                @foreach (request()->except(['page', 'per_halaman']) as $kunci => $nilai)
                    @if (is_scalar($nilai))
                        <input type="hidden" name="{{ $kunci }}" value="{{ $nilai }}">
                    @endif
                @endforeach
                <label for="per_halaman" class="sr-only">Jumlah data per halaman</label>
                <select
                    id="per_halaman"
                    name="per_halaman"
                    x-on:change="$el.form.submit()"
                    class="min-h-10 rounded-lg border border-border bg-surface py-1 pr-8 pl-3 text-base sm:text-sm"
                >
                    @foreach ([10, 25, 50] as $jumlah)
                        <option value="{{ $jumlah }}" @selected($data->perPage() === $jumlah)>{{ $jumlah }} / halaman</option>
                    @endforeach
                </select>
                <noscript><x-tombol varian="outline" type="submit">Terapkan</x-tombol></noscript>
            </form>
        </div>
    </div>
@endif
