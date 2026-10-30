@props(['aksi', 'nama'])

{{-- Membuka modal konfirmasi hapus (x-modal-hapus di layout) dengan alamat form dan nama data ini. --}}
<x-tombol-ikon
    ikon="heroicon-o-trash"
    :label="'Hapus '.$nama"
    varian="bahaya"
    x-on:click="$dispatch('konfirmasi-hapus', { aksi: {{ Js::from($aksi) }}, nama: {{ Js::from($nama) }} })"
    {{ $attributes }}
/>
