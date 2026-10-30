<x-layouts.app :judul="$mataKuliah->exists ? 'Ubah Mata Kuliah' : 'Tambah Mata Kuliah'">
    <x-kartu class="max-w-3xl">
        <p class="mb-5 text-sm text-text-muted">
            Kurikulum: <span class="font-medium text-text">{{ $kurikulum->nama_kurikulum }}</span>
        </p>
        @error('kurikulum_id')
            <p class="mb-5 text-sm text-danger">{{ $message }}</p>
        @enderror

        <form method="POST" action="{{ $mataKuliah->exists ? route('admin.mata-kuliah.update', $mataKuliah) : route('admin.mata-kuliah.store') }}" novalidate>
            @csrf
            @if ($mataKuliah->exists)
                @method('PUT')
            @else
                <input type="hidden" name="kurikulum_id" value="{{ $kurikulum->kurikulum_id }}">
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <x-input label="Kode Mata Kuliah" name="kode_mata_kuliah" :value="$mataKuliah->kode_mata_kuliah" keterangan="Unik dalam satu kurikulum." />
                <x-input label="Jenis Mata Kuliah" name="jenis_mata_kuliah" :value="$mataKuliah->jenis_mata_kuliah" :saran="['Wajib', 'Pilihan']" />
                <div class="md:col-span-2">
                    <x-input label="Nama Mata Kuliah" name="nama_mata_kuliah" :value="$mataKuliah->nama_mata_kuliah" />
                </div>
                <x-input label="Semester" name="semester" type="number" inputmode="numeric" min="1" max="8" :value="$mataKuliah->semester" keterangan="1 sampai 8." />
                <x-input label="Jumlah SKS" name="jumlah_sks" type="number" inputmode="numeric" min="1" max="24" :value="$mataKuliah->jumlah_sks" />
                <x-input label="Level Bloom" name="level_bloom_mk" :value="$mataKuliah->level_bloom_mk" :saran="['C1', 'C2', 'C3', 'C4', 'C5', 'C6']" keterangan="Boleh kosong." />
                <div class="md:col-span-2">
                    <x-textarea label="Keterangan" name="keterangan" :value="$mataKuliah->keterangan" keterangan="Boleh kosong." />
                </div>
            </div>

            <x-form-aksi :batal="route('admin.mata-kuliah.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
