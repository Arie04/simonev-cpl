<x-layouts.app :judul="$bahanKajian->exists ? 'Ubah Bahan Kajian' : 'Tambah Bahan Kajian'">
    <x-kartu class="max-w-3xl">
        <p class="mb-5 text-sm text-text-muted">
            Kurikulum: <span class="font-medium text-text">{{ $kurikulum->nama_kurikulum }}</span>
        </p>
        @error('kurikulum_id')
            <p class="mb-5 text-sm text-danger">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ $bahanKajian->exists ? route('admin.bahan-kajian.update', $bahanKajian) : route('admin.bahan-kajian.store') }}" novalidate>
            @csrf
            @if ($bahanKajian->exists)
                @method('PUT')
            @else
                <input type="hidden" name="kurikulum_id" value="{{ $kurikulum->kurikulum_id }}">
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <x-input label="Kode BK" name="kode_bk" :value="$bahanKajian->kode_bk" keterangan="Unik dalam satu kurikulum, mis. BK01." />
                <x-input label="Kategori BK" name="kategori_bk" :value="$bahanKajian->kategori_bk" :saran="['Kompetensi Utama', 'Softskill', 'Umum']" keterangan="Boleh kosong." />
                <div class="md:col-span-2">
                    <x-input label="Nama Bahan Kajian" name="nama_bahan_kajian" :value="$bahanKajian->nama_bahan_kajian" />
                </div>
                <x-input label="Bobot Minimum" name="bobot_min" type="number" inputmode="numeric" min="0" :value="$bahanKajian->bobot_min" keterangan="Tingkat kepentingan menurut CC2020 (bilangan bulat). Boleh kosong." />
                <x-input label="Bobot Maksimum" name="bobot_max" type="number" inputmode="numeric" min="0" :value="$bahanKajian->bobot_max" keterangan="Tidak boleh lebih kecil dari bobot minimum." />
                <x-input label="CS Core" name="cs_core" type="number" inputmode="numeric" min="0" :value="$bahanKajian->cs_core" keterangan="Jam menurut CS2023. Kosongkan jika BK tidak bersumber dari CS2023." />
                <x-input label="KA Core" name="ka_core" type="number" inputmode="numeric" min="0" :value="$bahanKajian->ka_core" keterangan="Jam menurut CS2023. Isi 0 jika memang 0 jam." />
                <div class="md:col-span-2">
                    <x-input label="Referensi" name="referensi" :value="$bahanKajian->referensi" :saran="['CS2023', 'CC2020']" keterangan="Boleh kosong. Kolom-kolom ini hanya dokumentasi kurikulum (Tabel 4 APTIKOM) dan tidak dipakai dalam perhitungan capaian." />
                </div>
            </div>

            <x-form-aksi :batal="route('admin.bahan-kajian.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
