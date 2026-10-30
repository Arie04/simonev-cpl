@use('App\Support\Angka')

<x-layouts.app :judul="$subCpmk->exists ? 'Ubah Sub-CPMK' : 'Tambah Sub-CPMK'">
    <x-kartu class="max-w-3xl">
        <p class="mb-5 text-sm text-text-muted">
            Kurikulum: <span class="font-medium text-text">{{ $kurikulum->nama_kurikulum }}</span>
        </p>
        @error('kurikulum_id')
            <p class="mb-5 text-sm text-danger">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ $subCpmk->exists ? route('admin.sub-cpmk.update', $subCpmk) : route('admin.sub-cpmk.store') }}" novalidate>
            @csrf
            @if ($subCpmk->exists)
                @method('PUT')
            @else
                <input type="hidden" name="kurikulum_id" value="{{ $kurikulum->kurikulum_id }}">
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-select
                        label="Pasangan Mata Kuliah · CPMK"
                        name="pemetaan_cpmk_mk_id"
                        :pilihan="$daftarPasangan"
                        :value="$subCpmk->pemetaan_cpmk_mk_id"
                        kosong="Pilih pasangan hasil pemetaan CPMK–MK"
                        keterangan="Sub-CPMK menempel ke pasangan CPMK–mata kuliah, bukan ke CPMK langsung."
                    />
                </div>
                <x-input label="Kode Sub-CPMK" name="kode_sub_cpmk" :value="$subCpmk->kode_sub_cpmk" keterangan="Unik dalam satu pasangan CPMK–MK, mis. Sub-CPMK0211." />
                <x-input
                    label="Bobot Sub-CPMK (%)"
                    name="bobot_sub_cpmk"
                    inputmode="decimal"
                    :value="Angka::ringkas($subCpmk->bobot_sub_cpmk)"
                    keterangan="Target bobot dalam nilai akhir MK. Jumlah bobot Sub-CPMK satu pasangan seharusnya sama dengan bobot CPMK-nya."
                />
                <div class="md:col-span-2">
                    <x-textarea label="Deskripsi Sub-CPMK" name="deskripsi_sub_cpmk" :value="$subCpmk->deskripsi_sub_cpmk" rows="3" />
                </div>
                <div class="md:col-span-2">
                    <x-textarea label="Materi" name="materi" :value="$subCpmk->materi" keterangan="Boleh kosong." />
                </div>
                <x-input label="Pertemuan Mulai" name="pertemuan_mulai" type="number" inputmode="numeric" min="1" max="16" :value="$subCpmk->pertemuan_mulai" keterangan="1 sampai 16. Boleh kosong." />
                <x-input label="Pertemuan Selesai" name="pertemuan_selesai" type="number" inputmode="numeric" min="1" max="16" :value="$subCpmk->pertemuan_selesai" />
                <div class="md:col-span-2">
                    <x-textarea label="Catatan" name="catatan" :value="$subCpmk->catatan" keterangan="Boleh kosong." />
                </div>
            </div>

            <x-form-aksi :batal="route('admin.sub-cpmk.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
