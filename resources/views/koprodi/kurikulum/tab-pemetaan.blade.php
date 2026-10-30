@use('App\Services\MatriksPemetaan')

<form method="GET" class="mb-4 flex flex-col gap-3 md:flex-row md:items-end">
    <input type="hidden" name="tab" value="pemetaan">
    <input type="hidden" name="kurikulum" value="{{ $kurikulum->kurikulum_id }}">
    <div class="md:w-80">
        <x-select name="jenis" label="Jenis Pemetaan" :pilihan="MatriksPemetaan::JENIS" :value="$matriks->jenis" x-on:change="$el.form.submit()" />
    </div>
    <x-tombol varian="outline" type="submit">Tampilkan</x-tombol>
</form>

<x-matriks-pemetaan :matriks="$matriks" />
