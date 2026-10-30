{{-- Satu baris tabel; menjadi kartu di layar < 768px. --}}
<tr {{ $attributes->class('border-t border-border align-top hover:bg-bg max-md:block max-md:rounded-xl max-md:border max-md:p-4 max-md:hover:bg-surface') }}>
    {{ $slot }}
</tr>
