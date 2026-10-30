@use('App\Http\Controllers\Koprodi\DataKurikulumController')

<x-layouts.app judul="Data Kurikulum">
    <x-kartu class="mb-6">
        <form method="GET" class="flex flex-col gap-3 md:flex-row md:items-end">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="md:w-80">
                <x-select
                    name="kurikulum"
                    label="Kurikulum"
                    :pilihan="$daftarKurikulum->pluck('nama_kurikulum', 'kurikulum_id')"
                    :value="$kurikulum?->kurikulum_id"
                    x-on:change="$el.form.submit()"
                />
            </div>
            <x-tombol varian="outline" type="submit">Tampilkan</x-tombol>
        </form>
    </x-kartu>

    @if ($kurikulum === null)
        <x-kartu>
            <x-kosong pesan="Belum ada data kurikulum." />
        </x-kartu>
    @else
        <x-kartu>
            <x-tab :daftar="DataKurikulumController::TAB" :aktif="$tab" />

            @include('koprodi.kurikulum.tab-'.$tab, $data)
        </x-kartu>
    @endif
</x-layouts.app>
