<x-layouts.app :judul="$programStudi->exists ? 'Ubah Program Studi' : 'Tambah Program Studi'">
    <x-kartu class="max-w-3xl">
        <form method="POST" action="{{ $programStudi->exists ? route('admin.program-studi.update', $programStudi) : route('admin.program-studi.store') }}" novalidate>
            @csrf
            @if ($programStudi->exists)
                @method('PUT')
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <x-input label="Nama Perguruan Tinggi" name="nama_perguruan_tinggi" :value="$programStudi->nama_perguruan_tinggi" />
                <x-input label="Nama Fakultas" name="nama_fakultas" :value="$programStudi->nama_fakultas" />
                <x-input label="Nama Program Studi" name="nama_program_studi" :value="$programStudi->nama_program_studi" />
                <x-input label="Jenjang Pendidikan" name="jenjang_pendidikan" :value="$programStudi->jenjang_pendidikan" :saran="['S1', 'S2', 'D3']" />
                <x-input label="Gelar Lulusan" name="gelar_lulusan" :value="$programStudi->gelar_lulusan" keterangan="Mis. S.Kom." />
                <x-input label="Peringkat Akreditasi" name="peringkat_akreditasi" :value="$programStudi->peringkat_akreditasi" :saran="['Unggul', 'Baik Sekali', 'Baik']" keterangan="Boleh kosong." />
                <div class="md:col-span-2">
                    <x-input label="Izin Pendirian" name="izin_pendirian" :value="$programStudi->izin_pendirian" keterangan="Nomor SK izin pendirian. Boleh kosong." />
                </div>
                <div class="md:col-span-2">
                    <x-textarea label="Visi Keilmuan" name="visi_keilmuan" :value="$programStudi->visi_keilmuan" />
                </div>
                <div class="md:col-span-2">
                    <x-textarea label="Misi Program Studi" name="misi_program_studi" :value="$programStudi->misi_program_studi" rows="5" />
                </div>
            </div>

            <x-form-aksi :batal="route('admin.program-studi.index')" />
        </form>
    </x-kartu>
</x-layouts.app>
