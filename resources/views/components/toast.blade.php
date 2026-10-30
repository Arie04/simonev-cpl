{{-- Pesan sukses/gagal dari session('sukses') atau session('gagal'), tampil sebagai toast (bagian 7.1 spesifikasi). --}}
@php
    $berhasil = session()->has('sukses');
    $pesan = session('sukses') ?? session('gagal');
@endphp

@if ($pesan)
    <div
        x-data="{ tampil: true }"
        x-init="setTimeout(() => tampil = false, 5000)"
        x-show="tampil"
        x-transition.opacity
        role="status"
        class="fixed inset-x-4 bottom-4 z-50 sm:inset-x-auto sm:right-6 sm:bottom-6 sm:w-96"
    >
        <div @class([
            'flex items-start gap-3 rounded-xl border p-4 text-sm shadow-lg',
            'border-success/20 bg-success-bg text-success' => $berhasil,
            'border-danger/20 bg-danger-bg text-danger' => ! $berhasil,
        ])>
            @if ($berhasil)
                <x-heroicon-o-check-circle class="size-5 shrink-0" />
            @else
                <x-heroicon-o-x-circle class="size-5 shrink-0" />
            @endif
            <p class="flex-1 font-medium">{{ $pesan }}</p>
            <button type="button" @click="tampil = false" class="-m-1 rounded p-1 hover:bg-white/60" aria-label="Tutup pesan">
                <x-heroicon-o-x-mark class="size-4" />
            </button>
        </div>
    </div>
@endif
