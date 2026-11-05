<x-layouts.app :judul="$dosen->exists ? 'Ubah Dosen' : 'Tambah Dosen'">
    <x-kartu class="max-w-3xl">
        <form method="POST" action="{{ $dosen->exists ? route('admin.dosen.update', $dosen) : route('admin.dosen.store') }}" novalidate>
            @csrf
            @if ($dosen->exists)
                @method('PUT')
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input label="Nama Dosen" name="nama" :value="$dosen->nama" />
                </div>
                <x-input label="NIDN" name="nidn" inputmode="numeric" :value="$dosen->nidn" keterangan="Dipakai untuk login." />
                <x-input label="Email" name="email" type="email" :value="$dosen->user?->email" keterangan="Boleh kosong. Dosen bisa login dengan NIDN." />
                @unless ($dosen->exists)
                    <div class="md:col-span-2">
                        <x-input label="Password Awal" name="password" type="password" autocomplete="new-password" keterangan="Minimal 8 karakter. Dosen bisa menggantinya lewat halaman Profil." />
                    </div>
                @endunless
            </div>

            <x-form-aksi :batal="route('admin.dosen.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
