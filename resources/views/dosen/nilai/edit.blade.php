@use('App\Support\Angka')

<x-layouts.app judul="Input Nilai Asesmen">
    {{-- Kartu filter bertingkat. Di mobile panel filter bisa dibuka-tutup (bagian 7.4). --}}
    <x-kartu class="mb-6" x-data="{ terbuka: {{ Js::from($asesmen === null) }} }">
        <div class="flex items-center justify-between gap-3 md:hidden">
            <p class="min-w-0 text-sm">
                @if ($asesmen)
                    <span class="font-semibold">{{ $kelas->mataKuliah->kode_mata_kuliah }} · Kelas {{ $kelas->nama_kelas }}</span>
                    <span class="block text-text-muted">{{ $asesmen->nama_asesmen }} · {{ $kelas->periodeAkademik->nama() }}</span>
                @else
                    <span class="text-text-muted">Pilih kelas dan asesmen.</span>
                @endif
            </p>
            <x-tombol varian="outline" x-on:click="terbuka = ! terbuka" x-bind:aria-expanded="terbuka">
                <x-heroicon-o-funnel class="size-5" />
                Filter
            </x-tombol>
        </div>

        <form
            method="GET"
            action="{{ route('dosen.nilai.edit') }}"
            class="hidden gap-4 md:grid md:grid-cols-2 xl:grid-cols-4"
            x-bind:class="{ 'max-md:mt-4 max-md:grid': terbuka }"
        >
            <x-select name="mata_kuliah" label="Mata Kuliah" :pilihan="$pilihanMataKuliah" :value="$mataKuliahId" kosong="Pilih mata kuliah" x-on:change="$el.form.submit()" />
            <x-select name="periode" label="Periode" :pilihan="$pilihanPeriode" :value="$periodeId" kosong="Pilih periode" x-on:change="$el.form.submit()" />
            <x-select name="kelas" label="Kelas" :pilihan="$pilihanKelas" :value="$kelas?->kelas_kuliah_id" kosong="Pilih kelas" x-on:change="$el.form.submit()" />
            <x-select name="asesmen" label="Rencana Asesmen" :pilihan="$pilihanAsesmen" :value="$asesmen?->rencana_asesmen_id" kosong="Pilih asesmen" x-on:change="$el.form.submit()" />
            <noscript><x-tombol varian="outline" type="submit">Tampilkan</x-tombol></noscript>
        </form>
    </x-kartu>

    @if (! $adaKelasDiampu)
        <x-kartu>
            <x-kosong pesan="Anda belum mengampu kelas." />
        </x-kartu>
    @elseif ($asesmen === null)
        <x-kartu>
            <x-kosong :pesan="$kelas && $pilihanAsesmen->isEmpty() ? 'Kelas ini belum punya rencana asesmen.' : 'Pilih mata kuliah, periode, kelas, dan rencana asesmen untuk menginput nilai.'">
                @if ($kelas && $pilihanAsesmen->isEmpty())
                    <x-tombol varian="outline" :href="route('dosen.kelas.rencana-asesmen.index', $kelas)">Buka Rencana Asesmen</x-tombol>
                @endif
            </x-kosong>
        </x-kartu>
    @else
        <x-kartu>
            <div class="mb-4 flex flex-col gap-1">
                <h2 class="text-lg font-semibold">{{ $asesmen->nama_asesmen }} · {{ $kelas->nama() }}</h2>
                <p class="text-sm text-text-muted">
                    {{ $kelas->periodeAkademik->nama() }} · nilai maksimum {{ Angka::ringkas($asesmen->nilai_maksimum) }} ·
                    {{ $nilaiTersimpan->count() }} dari {{ $daftarPeserta->count() }} peserta sudah dinilai.
                    Kosongkan isian untuk menghapus nilai yang belum benar.
                </p>
            </div>

            @error('nilai')
                <p class="mb-4 rounded-lg bg-danger-bg px-4 py-3 text-sm text-danger">{{ $message }}</p>
            @enderror
            @if ($errors->has('nilai.*'))
                <p class="mb-4 rounded-lg bg-danger-bg px-4 py-3 text-sm text-danger">
                    Ada nilai yang tidak valid, jadi tidak ada nilai yang disimpan. Perbaiki baris yang ditandai merah.
                </p>
            @endif

            @if ($daftarPeserta->isEmpty())
                <x-kosong pesan="Kelas ini belum punya peserta." />
            @else
                <form
                    method="POST"
                    action="{{ route('dosen.nilai.update', $asesmen) }}"
                    x-data="formNilai"
                    x-on:input="berubah = true"
                    x-on:submit="berubah = false"
                    novalidate
                >
                    @csrf
                    @method('PUT')

                    <x-tabel :kolom="['NPM', 'Nama Mahasiswa', 'Nilai', 'Status']">
                        @foreach ($daftarPeserta as $peserta)
                            @php
                                $id = $peserta->peserta_kelas_id;
                                $adaError = $errors->has('nilai.'.$id);
                            @endphp
                            <x-tabel.baris :class="$adaError ? 'bg-danger-bg max-md:border-danger' : ''">
                                <x-tabel.sel label="NPM" class="whitespace-nowrap">{{ $peserta->mahasiswa->nim }}</x-tabel.sel>
                                <x-tabel.sel label="Nama Mahasiswa" class="font-medium">{{ $peserta->mahasiswa->nama }}</x-tabel.sel>
                                <x-tabel.sel label="Nilai">
                                    <span class="inline-block text-left">
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            name="nilai[{{ $id }}]"
                                            value="{{ old('nilai.'.$id, Angka::ringkas($nilaiTersimpan[$id] ?? null)) }}"
                                            aria-label="Nilai {{ $peserta->mahasiswa->nama }}"
                                            @if ($adaError) aria-invalid="true" @endif
                                            @class([
                                                'block min-h-11 w-28 rounded-lg border bg-surface px-3 text-right text-base sm:text-sm',
                                                'border-danger' => $adaError,
                                                'border-border' => ! $adaError,
                                            ])
                                        >
                                        @error('nilai.'.$id)
                                            <span class="mt-1 block max-w-48 text-xs text-danger">{{ $message }}</span>
                                        @enderror
                                    </span>
                                </x-tabel.sel>
                                <x-tabel.sel label="Status">
                                    @if ($nilaiTersimpan->has($id))
                                        <x-badge varian="sukses" ikon="heroicon-o-check">Tersimpan</x-badge>
                                    @else
                                        <x-badge ikon="heroicon-o-minus">Belum</x-badge>
                                    @endif
                                </x-tabel.sel>
                            </x-tabel.baris>
                        @endforeach
                    </x-tabel>

                    {{-- Tombol Simpan Nilai menempel di bawah layar pada mobile (bagian 7.4). --}}
                    <div class="sticky bottom-0 z-30 -mx-5 mt-4 flex justify-end border-t border-border bg-surface px-5 py-3 shadow-[0_-4px_12px_rgb(15_23_42/0.06)] sm:-mx-6 sm:px-6 md:static md:mx-0 md:border-0 md:px-0 md:pb-0 md:shadow-none">
                        <x-tombol type="submit" class="w-full md:w-auto">
                            <x-heroicon-o-check class="size-5" />
                            Simpan Nilai
                        </x-tombol>
                    </div>
                </form>
            @endif
        </x-kartu>
    @endif
</x-layouts.app>
