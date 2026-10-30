{{-- Modal konfirmasi hapus, dipasang sekali di layout (bagian 7.1 spesifikasi). Dibuka oleh
     x-tombol-hapus lewat event 'konfirmasi-hapus'. Di mobile tampil sebagai bottom sheet,
     di desktop sebagai dialog di tengah (bagian 7.4). --}}
<div
    x-data="{ terbuka: false, aksi: '', nama: '' }"
    x-on:konfirmasi-hapus.window="aksi = $event.detail.aksi; nama = $event.detail.nama; terbuka = true; $nextTick(() => $refs.batal.focus())"
    x-on:keydown.escape.window="terbuka = false"
    x-show="terbuka"
    x-cloak
    class="fixed inset-0 z-60 flex items-end justify-center sm:items-center sm:p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="judul-modal-hapus"
>
    <div x-show="terbuka" x-transition.opacity x-on:click="terbuka = false" class="absolute inset-0 bg-text/50"></div>

    <div
        x-show="terbuka"
        x-transition
        class="relative w-full rounded-t-2xl bg-surface p-6 shadow-lg sm:max-w-md sm:rounded-2xl"
    >
        <div class="flex items-start gap-4">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-danger-bg text-danger">
                <x-heroicon-o-exclamation-triangle class="size-6" />
            </span>
            <div class="min-w-0">
                <h2 id="judul-modal-hapus" class="text-lg font-semibold">Hapus data?</h2>
                <p class="mt-1 text-sm text-text-muted">
                    Data <strong class="break-words text-text" x-text="nama"></strong> akan dihapus.
                    Tindakan ini tidak bisa dibatalkan.
                </p>
            </div>
        </div>

        <form method="POST" x-bind:action="aksi" class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            @csrf
            @method('DELETE')
            <x-tombol varian="outline" x-ref="batal" x-on:click="terbuka = false">Batal</x-tombol>
            <x-tombol varian="bahaya" type="submit">
                <x-heroicon-o-trash class="size-5" />
                Hapus
            </x-tombol>
        </form>
    </div>
</div>
