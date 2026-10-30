@props(['batal', 'simpan' => 'Simpan'])

{{-- Tombol di bawah form: Batal (outline) dan Simpan (tombol utama). --}}
<div class="mt-6 flex flex-col-reverse gap-3 border-t border-border pt-5 sm:flex-row sm:justify-end">
    <x-tombol varian="outline" :href="$batal">Batal</x-tombol>
    <x-tombol type="submit">{{ $simpan }}</x-tombol>
</div>
