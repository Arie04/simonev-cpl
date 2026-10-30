<x-layouts.app :judul="$user->exists ? 'Ubah Akun' : 'Tambah Akun'">
    <div class="max-w-3xl space-y-6">
        <x-kartu judul="Data Akun">
            @if ($user->dosen || $user->mahasiswa)
                <p class="mb-5 text-sm text-text-muted">
                    Akun ini milik
                    @if ($user->dosen)
                        dosen dengan NIDN <span class="font-medium text-text">{{ $user->dosen->nidn }}</span>.
                    @else
                        mahasiswa dengan NPM <span class="font-medium text-text">{{ $user->mahasiswa->nim }}</span>.
                    @endif
                    Perubahan nama ikut tersimpan di data {{ $user->dosen ? 'dosen' : 'mahasiswa' }}.
                </p>
            @endif

            <form method="POST" action="{{ $user->exists ? route('admin.akun.update', $user) : route('admin.akun.store') }}" novalidate>
                @csrf
                @if ($user->exists)
                    @method('PUT')
                @endif

                <div class="grid gap-5 md:grid-cols-2">
                    <x-input label="Nama" name="nama" :value="$user->nama" />
                    <x-input
                        label="Email"
                        name="email"
                        type="email"
                        :value="$user->email"
                        :keterangan="$user->dosen || $user->mahasiswa ? 'Boleh kosong karena bisa login dengan NIDN/NPM.' : 'Dipakai untuk login.'"
                    />
                    @unless ($user->exists)
                        <div class="md:col-span-2">
                            <x-input label="Password Awal" name="password" type="password" autocomplete="new-password" keterangan="Minimal 8 karakter." />
                        </div>
                    @endunless
                    <div class="md:col-span-2">
                        <x-kotak-centang
                            label="Peran"
                            name="peran"
                            :pilihan="$pilihanPeran"
                            :terpilih="$peranTerpilih"
                            keterangan="Peran Dosen dan Mahasiswa hanya untuk akun yang punya data dosen atau mahasiswa."
                        />
                    </div>
                </div>

                <x-form-aksi :batal="route('admin.akun.index')" />
            </form>
        </x-kartu>

        @if ($user->exists)
            {{-- Tidak ada reset password lewat email; pengguna yang lupa password menghubungi Admin (bagian 6). --}}
            <x-kartu judul="Reset Password">
                <form method="POST" action="{{ route('admin.akun.password.update', $user) }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="grid gap-5 md:grid-cols-2">
                        <x-input label="Password Baru" name="password_baru" type="password" autocomplete="new-password" keterangan="Minimal 8 karakter." />
                        <x-input label="Konfirmasi Password Baru" name="password_baru_confirmation" type="password" autocomplete="new-password" />
                    </div>

                    <div class="mt-6 flex justify-end border-t border-border pt-5">
                        <x-tombol varian="outline" type="submit">
                            <x-heroicon-o-key class="size-5" />
                            Reset Password
                        </x-tombol>
                    </div>
                </form>
            </x-kartu>
        @endif
    </div>
</x-layouts.app>
