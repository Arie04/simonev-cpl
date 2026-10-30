@use('App\Services\MatriksPemetaan')

<x-layouts.app judul="Pemetaan Kurikulum">
    <x-kartu class="mb-6">
        <form method="GET" class="grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <x-select name="jenis" label="Jenis Pemetaan" :pilihan="MatriksPemetaan::JENIS" :value="$jenis" x-on:change="$el.form.submit()" />
            <x-select
                name="kurikulum"
                label="Kurikulum"
                :pilihan="$daftarKurikulum->pluck('nama_kurikulum', 'kurikulum_id')"
                :value="$kurikulum?->kurikulum_id"
                x-on:change="$el.form.submit()"
            />
            <x-tombol varian="outline" type="submit">Tampilkan</x-tombol>
        </form>
    </x-kartu>

    @if ($matriks === null)
        <x-kartu>
            <x-kosong
                pesan="Belum ada kurikulum. Tambahkan kurikulum terlebih dahulu."
                :tambah="route('admin.kurikulum.create')"
                label-tambah="Tambah Kurikulum"
            />
        </x-kartu>
    @else
        <x-kartu :judul="'Matriks '.MatriksPemetaan::JENIS[$jenis]">
            <p class="mb-4 text-sm text-text-muted">
                Klik lingkaran untuk menghubungkan {{ $matriks->judulBaris() }} dengan {{ $matriks->judulKolom() }}.
                @if ($jenis === 'cpmk-mk')
                    Isi bobot CPMK dalam mata kuliah, yaitu skor maksimum CPMK (Tabel 19 APTIKOM). Total per mata kuliah seharusnya 100.
                @elseif ($jenis === 'bk-mk')
                    Isi porsi bahan kajian dalam mata kuliah (persen). Bobot ini hanya dokumentasi kurikulum.
                @endif
            </p>

            @error('terhubung')
                <p class="mb-4 rounded-lg bg-danger-bg px-4 py-3 text-sm text-danger">{{ $message }}</p>
            @enderror
            @if ($errors->has('bobot.*'))
                <p class="mb-4 rounded-lg bg-danger-bg px-4 py-3 text-sm text-danger">Bobot harus berupa angka 0 sampai 100. Periksa isian yang ditandai merah.</p>
            @endif

            <form method="POST" action="{{ route('admin.pemetaan.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="jenis" value="{{ $jenis }}">
                <input type="hidden" name="kurikulum_id" value="{{ $kurikulum->kurikulum_id }}">

                <x-matriks-pemetaan :matriks="$matriks" :bisa-ubah="true" />

                @if ($matriks->daftarBaris->isNotEmpty() && $matriks->daftarKolom->isNotEmpty())
                    {{-- Tombol utama menempel di bawah layar pada mobile (bagian 7.6.5). --}}
                    <div class="sticky bottom-0 z-30 -mx-5 mt-4 flex justify-end border-t border-border bg-surface px-5 py-3 shadow-[0_-4px_12px_rgb(15_23_42/0.06)] sm:-mx-6 sm:px-6 md:static md:mx-0 md:border-0 md:px-0 md:pb-0 md:shadow-none">
                        <x-tombol type="submit" class="w-full md:w-auto">
                            <x-heroicon-o-check class="size-5" />
                            Simpan Pemetaan
                        </x-tombol>
                    </div>
                @endif
            </form>
        </x-kartu>
    @endif
</x-layouts.app>
