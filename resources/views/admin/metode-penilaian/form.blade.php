<x-layouts.app :judul="$metodePenilaian->exists ? 'Ubah Metode Penilaian' : 'Tambah Metode Penilaian'">
    <x-kartu class="max-w-3xl">
        <form method="POST" action="{{ $metodePenilaian->exists ? route('admin.metode-penilaian.update', $metodePenilaian) : route('admin.metode-penilaian.store') }}" novalidate>
            @csrf
            @if ($metodePenilaian->exists)
                @method('PUT')
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <x-input label="Kode Metode" name="kode_metode" :value="$metodePenilaian->kode_metode" keterangan="Unik, mis. UTS." />
                <x-input label="Nama Metode" name="nama_metode" :value="$metodePenilaian->nama_metode" keterangan="Mis. Ujian Tengah Semester." />
            </div>

            <x-form-aksi :batal="route('admin.metode-penilaian.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
