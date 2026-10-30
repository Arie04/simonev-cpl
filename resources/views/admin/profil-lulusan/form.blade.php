<x-layouts.app :judul="$profilLulusan->exists ? 'Ubah Profil Lulusan' : 'Tambah Profil Lulusan'">
    <x-kartu class="max-w-3xl">
        <p class="mb-5 text-sm text-text-muted">
            Kurikulum: <span class="font-medium text-text">{{ $kurikulum->nama_kurikulum }}</span>
        </p>
        @error('kurikulum_id')
            <p class="mb-5 text-sm text-danger">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ $profilLulusan->exists ? route('admin.profil-lulusan.update', $profilLulusan) : route('admin.profil-lulusan.store') }}" novalidate>
            @csrf
            @if ($profilLulusan->exists)
                @method('PUT')
            @else
                <input type="hidden" name="kurikulum_id" value="{{ $kurikulum->kurikulum_id }}">
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <x-input label="Kode PL" name="kode_pl" :value="$profilLulusan->kode_pl" keterangan="Unik dalam satu kurikulum, mis. PL01." />
                <x-input label="Unsur" name="unsur" :value="$profilLulusan->unsur" keterangan="Boleh kosong." />
                <div class="md:col-span-2">
                    <x-textarea label="Deskripsi Profil Lulusan" name="deskripsi_pl" :value="$profilLulusan->deskripsi_pl" rows="4" />
                </div>
                <div class="md:col-span-2">
                    <x-input label="Sumber" name="sumber" :value="$profilLulusan->sumber" keterangan="Boleh kosong." />
                </div>
                <div class="md:col-span-2">
                    <x-kotak-centang
                        label="Profesi"
                        name="profesi"
                        :pilihan="$daftarProfesi"
                        :terpilih="$profesiTerpilih"
                        kosong="Belum ada profesi di kurikulum ini. Tambahkan lewat menu Profesi."
                    />
                </div>
            </div>

            <x-form-aksi :batal="route('admin.profil-lulusan.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
