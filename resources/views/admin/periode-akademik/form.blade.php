@use('App\Models\PeriodeAkademik')

<x-layouts.app :judul="$periodeAkademik->exists ? 'Ubah Periode Akademik' : 'Tambah Periode Akademik'">
    <x-kartu class="max-w-3xl">
        <form method="POST" action="{{ $periodeAkademik->exists ? route('admin.periode-akademik.update', $periodeAkademik) : route('admin.periode-akademik.store') }}" novalidate>
            @csrf
            @if ($periodeAkademik->exists)
                @method('PUT')
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <x-input label="Tahun Akademik" name="tahun_akademik" :value="$periodeAkademik->tahun_akademik" placeholder="2026/2027" keterangan="Dua tahun berurutan, mis. 2026/2027." />
                <x-select
                    label="Semester"
                    name="semester_periode"
                    :pilihan="[PeriodeAkademik::GANJIL => PeriodeAkademik::GANJIL, PeriodeAkademik::GENAP => PeriodeAkademik::GENAP]"
                    :value="$periodeAkademik->semester_periode"
                    kosong="Pilih semester"
                />
            </div>

            <x-form-aksi :batal="route('admin.periode-akademik.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
