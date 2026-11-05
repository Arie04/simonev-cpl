<x-layouts.app :judul="$mahasiswa->exists ? 'Ubah Mahasiswa' : 'Tambah Mahasiswa'">
    <x-kartu class="max-w-3xl">
        <form method="POST" action="{{ $mahasiswa->exists ? route('admin.mahasiswa.update', $mahasiswa) : route('admin.mahasiswa.store') }}" novalidate>
            @csrf
            @if ($mahasiswa->exists)
                @method('PUT')
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input label="Nama Mahasiswa" name="nama" :value="$mahasiswa->nama" />
                </div>
                <x-input label="NPM" name="nim" inputmode="numeric" :value="$mahasiswa->nim" keterangan="Dipakai untuk login." />
                <x-input label="Angkatan" name="angkatan" type="number" inputmode="numeric" min="2000" max="2100" :value="$mahasiswa->angkatan" />
                <div class="md:col-span-2">
                    <x-input label="Email" name="email" type="email" :value="$mahasiswa->user?->email" keterangan="Boleh kosong. Mahasiswa bisa login dengan NPM." />
                </div>
                @unless ($mahasiswa->exists)
                    <div class="md:col-span-2">
                        <x-input label="Password Awal" name="password" type="password" autocomplete="new-password" keterangan="Minimal 8 karakter. Mahasiswa bisa menggantinya lewat halaman Profil." />
                    </div>
                @endunless
            </div>

            <x-form-aksi :batal="route('admin.mahasiswa.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
