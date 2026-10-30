<x-layouts.app :judul="$kelas->exists ? 'Ubah Kelas' : 'Tambah Kelas'">
    <x-kartu class="max-w-3xl">
        <form method="POST" action="{{ $kelas->exists ? route('admin.kelas-kuliah.update', $kelas) : route('admin.kelas-kuliah.store') }}" novalidate>
            @csrf
            @if ($kelas->exists)
                @method('PUT')
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <x-select label="Periode Akademik" name="periode_akademik_id" :pilihan="$daftarPilihanPeriode" :value="$kelas->periode_akademik_id" kosong="Pilih periode" />
                <x-input label="Nama Kelas" name="nama_kelas" :value="$kelas->nama_kelas" maxlength="10" keterangan="Mis. A, B, atau C." />
                <div class="md:col-span-2">
                    <x-select
                        label="Mata Kuliah"
                        name="mata_kuliah_id"
                        :pilihan="$daftarPilihanMataKuliah"
                        :value="$kelas->mata_kuliah_id"
                        kosong="Pilih mata kuliah"
                        keterangan="Mata kuliah dari semua kurikulum, karena kelas kurikulum lama dan baru bisa berjalan bersamaan."
                    />
                </div>
            </div>

            <x-form-aksi :batal="$kelas->exists ? route('admin.kelas-kuliah.show', $kelas) : route('admin.kelas-kuliah.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
