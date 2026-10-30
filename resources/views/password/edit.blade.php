{{-- Ubah password (bagian 7.6 butir 13). --}}
<x-layouts.app judul="Ubah Password">
    <x-kartu class="max-w-xl">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4" novalidate>
            @csrf
            @method('PUT')

            <x-input label="Password Lama" name="password_lama" type="password" autocomplete="current-password" required />
            <x-input label="Password Baru" name="password" type="password" keterangan="Minimal 8 karakter." autocomplete="new-password" required />
            <x-input label="Konfirmasi Password Baru" name="password_confirmation" type="password" autocomplete="new-password" required />

            <div class="flex justify-end pt-2">
                <x-tombol type="submit">Simpan Password</x-tombol>
            </div>
        </form>
    </x-kartu>
</x-layouts.app>
