@props(['capaian', 'lulus'])

{{-- Badge status seorang mahasiswa: Lulus jika capaian ≥ AM (bagian 5.3). Selalu teks dan ikon. --}}
@if ($capaian === null)
    <x-badge ikon="heroicon-o-minus">Belum ada nilai</x-badge>
@elseif ($lulus === true)
    <x-badge varian="sukses" ikon="heroicon-o-check">Lulus</x-badge>
@elseif ($lulus === false)
    <x-badge varian="bahaya" ikon="heroicon-o-x-mark">Belum Lulus</x-badge>
@else
    <x-badge ikon="heroicon-o-minus">Ambang belum ditetapkan</x-badge>
@endif
