@use('App\Support\Angka')

@php
    $ambangLengkap = $ambang['am'] !== null && $ambang['ak'] !== null;
    $labelAk = $ambang['ak'] !== null ? Angka::ringkas($ambang['ak']).'%' : '';
    // Label kategori menyebut ambang yang berlaku (bagian 7.6.2).
    $kategori = $ringkasan === null ? [] : [
        ['nama' => 'Tercapai', 'syarat' => "≥ {$labelAk} mahasiswa lulus", 'jumlah' => $ringkasan['tercapai'], 'varian' => 'sukses', 'ikon' => 'heroicon-o-check'],
        ['nama' => 'Tidak Tercapai', 'syarat' => "< {$labelAk} mahasiswa lulus", 'jumlah' => $ringkasan['tidak_tercapai'], 'varian' => 'bahaya', 'ikon' => 'heroicon-o-x-mark'],
        ['nama' => 'Belum ada nilai', 'syarat' => null, 'jumlah' => $ringkasan['belum_ada_nilai'], 'varian' => 'netral', 'ikon' => 'heroicon-o-minus'],
    ];
    $labelGrafik = collect($kategori)->map(fn ($baris) => $baris['nama'].($baris['syarat'] ? " ({$baris['syarat']})" : ''));
@endphp

<x-layouts.app judul="Dashboard">
    <div class="-mt-3 mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <p class="text-text-muted">Selamat datang, {{ auth()->user()->nama }}. Monitoring capaian pembelajaran program studi.</p>
        {{-- Dropdown Kurikulum di kanan atas, bawaannya kurikulum terbaru (bagian 7.6.2). --}}
        <form method="GET" class="md:w-72">
            @if ($angkatan)
                <input type="hidden" name="angkatan" value="{{ $angkatan }}">
            @endif
            <x-select
                name="kurikulum"
                label="Kurikulum"
                :pilihan="$daftarKurikulum->pluck('nama_kurikulum', 'kurikulum_id')"
                :value="$kurikulum?->kurikulum_id"
                x-on:change="$el.form.submit()"
            />
            <noscript><x-tombol varian="outline" type="submit" class="mt-2">Tampilkan</x-tombol></noscript>
        </form>
    </div>

    {{-- Enam kartu statistik: 2 kolom di mobile, 3 kolom di desktop (bagian 7.4). --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
        <x-kartu-statistik ikon="heroicon-o-academic-cap" label="Mahasiswa" :nilai="$jumlah['mahasiswa']" keterangan="Seluruh prodi" />
        <x-kartu-statistik ikon="heroicon-o-book-open" label="Mata Kuliah" :nilai="$jumlah['mata_kuliah']" :keterangan="$kurikulum?->nama_kurikulum" />
        <x-kartu-statistik ikon="heroicon-o-building-library" label="Kelas Aktif" :nilai="$jumlah['kelas_aktif']" :keterangan="$periodeAktif ? 'Periode '.$periodeAktif->nama() : 'Belum ada kelas'" />
        <x-kartu-statistik ikon="heroicon-o-clipboard-document-check" label="CPMK" :nilai="$jumlah['cpmk']" :keterangan="$kurikulum?->nama_kurikulum" />
        <x-kartu-statistik ikon="heroicon-o-trophy" label="CPL" :nilai="$jumlah['cpl']" :keterangan="$kurikulum?->nama_kurikulum" />
        <x-kartu-statistik
            ikon="heroicon-o-calendar-days"
            label="Periode Akademik Aktif"
            :nilai="$periodeAktif?->tahun_akademik ?? '–'"
            :keterangan="$periodeAktif ? 'Semester '.$periodeAktif->semester_periode : 'Belum ada kelas'"
        />
    </div>

    <x-kartu judul="Ringkasan Capaian CPL" class="mt-6">
        <form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            @if ($kurikulum)
                <input type="hidden" name="kurikulum" value="{{ $kurikulum->kurikulum_id }}">
            @endif
            <div class="sm:w-64">
                <x-select name="angkatan" label="Angkatan" :pilihan="$pilihanAngkatan" :value="$angkatan" kosong="Semua angkatan" x-on:change="$el.form.submit()" />
            </div>
            <noscript><x-tombol varian="outline" type="submit">Tampilkan</x-tombol></noscript>
        </form>

        <p class="mb-4 text-xs text-text-muted">
            CPL kumulatif semua periode{{ $angkatan ? ', angkatan '.$angkatan : '' }}.
            @if ($ambangLengkap)
                Mahasiswa Lulus jika capaiannya ≥ {{ Angka::format($ambang['am']) }}; CPL Tercapai jika ≥ {{ $labelAk }} mahasiswa lulus.
            @endif
        </p>

        @if (! $ambangLengkap)
            <x-kosong pesan="Ambang ketercapaian belum ditetapkan program studi." />
        @elseif ($ringkasan === null || $ringkasan['total'] === 0)
            <x-kosong pesan="Kurikulum ini belum punya CPL." />
        @else
            <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] lg:items-center">
                <div
                    class="h-60 md:h-80"
                    x-data="grafikDonut({{ Js::from(['label' => $labelGrafik, 'nilai' => collect($kategori)->pluck('jumlah')]) }})"
                >
                    <canvas
                        x-ref="kanvas"
                        role="img"
                        aria-label="Donut ringkasan CPL: {{ $ringkasan['tercapai'] }} Tercapai, {{ $ringkasan['tidak_tercapai'] }} Tidak Tercapai, {{ $ringkasan['belum_ada_nilai'] }} belum ada nilai"
                    ></canvas>
                </div>

                <div class="overflow-x-auto rounded-lg border border-border">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-neutral-bg text-xs font-semibold text-text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Kategori</th>
                                <th scope="col" class="px-4 py-3 text-right">Jumlah CPL</th>
                                <th scope="col" class="px-4 py-3 text-right">Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kategori as $baris)
                                <tr class="border-t border-border">
                                    <td class="px-4 py-3">
                                        <x-badge :varian="$baris['varian']" :ikon="$baris['ikon']">{{ $baris['nama'] }}</x-badge>
                                        @if ($baris['syarat'])
                                            <span class="mt-1 block text-xs text-text-muted">{{ $baris['syarat'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ $baris['jumlah'] }}</td>
                                    <td class="px-4 py-3 text-right">{{ Angka::format($baris['jumlah'] / $ringkasan['total'] * 100) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-border bg-neutral-bg font-semibold">
                                <td class="px-4 py-3">Total</td>
                                <td class="px-4 py-3 text-right">{{ $ringkasan['total'] }}</td>
                                <td class="px-4 py-3 text-right">{{ Angka::format(100) }}%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif
    </x-kartu>
</x-layouts.app>
