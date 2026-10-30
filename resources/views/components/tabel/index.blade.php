@props(['kolom' => []])

{{-- Tabel daftar (bagian 7.3 spesifikasi). Di layar < 768px setiap baris tampil sebagai kartu
     berisi pasangan label–nilai (bagian 7.4); labelnya diambil dari atribut data-label tiap sel. --}}
<div class="relative overflow-x-auto md:rounded-lg md:border md:border-border">
    <table {{ $attributes->class('w-full text-left text-sm max-md:block') }}>
        <thead class="bg-neutral-bg text-xs font-semibold text-text-muted max-md:sr-only">
            <tr>
                @foreach ($kolom as $judulKolom)
                    <th scope="col" @class(['px-4 py-3', 'text-right' => $loop->last && $judulKolom === 'Aksi'])>{{ $judulKolom }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="max-md:block max-md:space-y-3">
            {{ $slot }}
        </tbody>
    </table>
</div>
