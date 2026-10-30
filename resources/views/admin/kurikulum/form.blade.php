<x-layouts.app :judul="$kurikulum->exists ? 'Ubah Kurikulum' : 'Tambah Kurikulum'">
    <x-kartu class="max-w-3xl">
        <form method="POST" action="{{ $kurikulum->exists ? route('admin.kurikulum.update', $kurikulum) : route('admin.kurikulum.store') }}" novalidate>
            @csrf
            @if ($kurikulum->exists)
                @method('PUT')
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-select label="Program Studi" name="program_studi_id" :pilihan="$daftarProgramStudi" :value="$kurikulum->program_studi_id" kosong="Pilih program studi" />
                </div>
                <x-input label="Nama Kurikulum" name="nama_kurikulum" :value="$kurikulum->nama_kurikulum" keterangan="Mis. Kurikulum OBE 2026." />
                <x-input label="Tahun Berlaku" name="tahun_berlaku" type="number" inputmode="numeric" min="2000" max="2100" :value="$kurikulum->tahun_berlaku" keterangan="Kurikulum terbaru ditentukan dari tahun ini." />
            </div>

            <x-form-aksi :batal="route('admin.kurikulum.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
