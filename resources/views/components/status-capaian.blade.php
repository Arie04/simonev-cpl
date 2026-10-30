@props(['rekap'])

{{-- Badge status sebuah item laporan. Status selalu memuat teks dan ikon, tidak hanya warna (bagian 7.2). --}}
@if ($rekap['jumlah_mahasiswa'] === 0)
    <x-badge ikon="heroicon-o-minus">Belum ada nilai</x-badge>
@elseif ($rekap['tercapai'] === true)
    <x-badge varian="sukses" ikon="heroicon-o-check">Tercapai</x-badge>
@elseif ($rekap['tercapai'] === false)
    <x-badge varian="bahaya" ikon="heroicon-o-x-mark">Tidak Tercapai</x-badge>
@else
    <x-badge ikon="heroicon-o-minus">Ambang belum ditetapkan</x-badge>
@endif
