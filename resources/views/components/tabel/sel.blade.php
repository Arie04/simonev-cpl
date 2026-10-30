@props(['label', 'panjang' => false])

{{-- Sel tabel. Di layar < 768px label kolom tampil di kiri dan nilainya di kanan; untuk teks
     panjang (panjang = true) labelnya di atas supaya teks tetap mudah dibaca. --}}
<td
    data-label="{{ $label }}"
    {{ $attributes->class([
        'px-4 py-3 max-md:px-0 max-md:py-1.5 max-md:before:font-medium max-md:before:text-text-muted max-md:before:content-[attr(data-label)]',
        'max-md:flex max-md:justify-between max-md:gap-4 max-md:text-right max-md:before:shrink-0 max-md:before:text-left' => ! $panjang,
        'max-md:block max-md:before:mb-0.5 max-md:before:block' => $panjang,
    ]) }}
>{{ $slot }}</td>
