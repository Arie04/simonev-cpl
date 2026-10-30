{{-- Sel tombol aksi (ubah, hapus). Di layar < 768px tombolnya pindah ke bawah kartu. --}}
<td {{ $attributes->class('px-4 py-2 max-md:mt-3 max-md:block max-md:border-t max-md:border-border max-md:px-0 max-md:pt-3 max-md:pb-0') }}>
    <div class="flex items-center justify-end gap-2">
        {{ $slot }}
    </div>
</td>
