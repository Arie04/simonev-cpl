@use('App\Services\LaporanCapaian')
@use('App\Support\Angka')

@php
    $ambangKelulusanAda = $ambang['am'] !== null;
    $baris = $hasil['baris'] ?? [];
    $ringkasan = $hasil['ringkasan'] ?? null;
    // Grafik: % mahasiswa Lulus per item; jika AM belum ditetapkan, rata-rata capaian (bagian 7.6.9).
    $dataGrafik = [
        'label' => collect($baris)->pluck('kode'),
        'nilai' => collect($baris)->map(fn ($satuBaris) => $ambangKelulusanAda ? $satuBaris['rekap']['persen_lulus'] : $satuBaris['rekap']['rata_rata']),
        'status' => collect($baris)->map(fn ($satuBaris) => match ($satuBaris['rekap']['tercapai']) {
            true => 'tercapai',
            false => 'tidak',
            null => 'netral',
        }),
        'labelNilai' => $ambangKelulusanAda ? '% mahasiswa lulus' : 'Rata-rata capaian',
        'ambangKetercapaian' => $ambangKelulusanAda ? $ambang['ak'] : null,
    ];
    $ringkasanFilter = collect([
        $filter->kurikulum?->nama_kurikulum,
        $filter->periode?->nama() ?? 'Semua periode',
        $filter->pilihan['mata_kuliah'][$filter->terpilih('mata_kuliah')] ?? null,
    ])->filter()->implode(' · ');
@endphp

<x-layouts.app judul="Laporan Capaian Pembelajaran">
    {{-- Kartu filter; di mobile bisa dibuka-tutup lewat tombol Filter (bagian 7.4). --}}
    <x-kartu class="mb-6" x-data="{ terbuka: false }">
        <div class="flex items-center justify-between gap-3 md:hidden">
            <p class="min-w-0 text-sm text-text-muted">{{ $ringkasanFilter }}</p>
            <x-tombol varian="outline" x-on:click="terbuka = ! terbuka" x-bind:aria-expanded="terbuka">
                <x-heroicon-o-funnel class="size-5" />
                Filter
            </x-tombol>
        </div>

        <form method="GET" action="{{ route($routeLaporan) }}" class="hidden md:block" x-bind:class="{ 'max-md:mt-4 max-md:block': terbuka }">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-select name="kurikulum" label="Kurikulum" :pilihan="$filter->pilihan['kurikulum']" :value="$filter->terpilih('kurikulum')" />
                <x-select name="periode" label="Periode" :pilihan="$filter->pilihan['periode'] ?? []" :value="$filter->terpilih('periode')" kosong="Semua periode" />
                <x-select name="angkatan" label="Angkatan" :pilihan="$filter->pilihan['angkatan'] ?? []" :value="$filter->terpilih('angkatan')" kosong="Semua angkatan" />
                <x-select name="mata_kuliah" label="Mata Kuliah" :pilihan="$filter->pilihan['mata_kuliah'] ?? []" :value="$filter->terpilih('mata_kuliah')" kosong="Semua mata kuliah" />
                <x-select
                    name="kelas"
                    label="Kelas"
                    :pilihan="$filter->pilihan['kelas'] ?? []"
                    :value="$filter->terpilih('kelas')"
                    kosong="Semua kelas"
                    :keterangan="empty($filter->pilihan['kelas']) ? 'Pilih mata kuliah dulu.' : null"
                />
                <x-select name="mahasiswa" label="Mahasiswa" :pilihan="$filter->pilihan['mahasiswa'] ?? []" :value="$filter->terpilih('mahasiswa')" kosong="Semua mahasiswa" />
                <x-select name="cpl" label="CPL" :pilihan="$filter->pilihan['cpl'] ?? []" :value="$filter->terpilih('cpl')" kosong="Semua CPL" />
                <x-select name="cpmk" label="CPMK" :pilihan="$filter->pilihan['cpmk'] ?? []" :value="$filter->terpilih('cpmk')" kosong="Semua CPMK" />
                <x-select name="sub_cpmk" label="Sub-CPMK" :pilihan="$filter->pilihan['sub_cpmk'] ?? []" :value="$filter->terpilih('sub_cpmk')" kosong="Semua Sub-CPMK" />
            </div>

            <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-tombol varian="outline" :href="route($routeLaporan, ['tab' => $tab])">Atur Ulang</x-tombol>
                <x-tombol type="submit">
                    <x-heroicon-o-funnel class="size-5" />
                    Terapkan Filter
                </x-tombol>
            </div>
        </form>
    </x-kartu>

    @if ($filter->kurikulum === null)
        <x-kartu>
            <x-kosong :pesan="$routeLaporan === 'dosen.laporan' ? 'Anda belum mengampu kelas.' : 'Belum ada data kurikulum.'" />
        </x-kartu>
    @else
        {{-- Ambang yang sedang berlaku (bagian 7.6.9). --}}
        <div class="mb-4 flex flex-col gap-1 rounded-lg border border-border bg-surface px-4 py-3 text-sm sm:flex-row sm:gap-6">
            <span>Ambang Batas Kelulusan Mahasiswa (AM): <strong>{{ $ambang['am'] !== null ? Angka::format($ambang['am']) : 'Belum ditetapkan' }}</strong></span>
            <span>Ambang Batas Ketercapaian (AK): <strong>{{ $ambang['ak'] !== null ? Angka::format($ambang['ak']).'%' : 'Belum ditetapkan' }}</strong></span>
        </div>

        <x-tab :daftar="LaporanCapaian::TAB" :aktif="$tab" />

        {{-- Keterangan cakupan periode (bagian 5.3, KD-23). --}}
        <p class="mb-4 text-xs text-text-muted">
            @if ($tab === 'cpl')
                {{ $filter->periode ? 'CPL dihitung dari kelas pada periode ini saja.' : 'CPL kumulatif semua periode.' }}
            @else
                {{ $filter->periode ? 'Capaian dihitung dari kelas pada periode ini saja.' : 'Capaian semua periode.' }}
            @endif
            Mahasiswa yang mengulang dihitung sekali per mata kuliah, dari kelas terbaru yang sudah bernilai.
        </p>

        {{-- Empat kartu ringkasan: 2 kolom di mobile, 4 kolom di desktop (bagian 7.4). --}}
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <x-kartu-statistik ikon="heroicon-o-chart-bar" label="Rata-rata Capaian" :nilai="$ringkasan['rata_rata'] !== null ? Angka::format($ringkasan['rata_rata']).'%' : '–'" />
            <x-kartu-statistik ikon="heroicon-o-arrow-trending-up" label="Capaian Tertinggi" :nilai="$ringkasan['tertinggi'] ? Angka::format($ringkasan['tertinggi']['rekap']['rata_rata']).'%' : '–'" :keterangan="$ringkasan['tertinggi']['kode'] ?? null" />
            <x-kartu-statistik ikon="heroicon-o-arrow-trending-down" label="Capaian Terendah" :nilai="$ringkasan['terendah'] ? Angka::format($ringkasan['terendah']['rekap']['rata_rata']).'%' : '–'" :keterangan="$ringkasan['terendah']['kode'] ?? null" />
            <x-kartu-statistik ikon="heroicon-o-list-bullet" label="Jumlah Item" :nilai="$ringkasan['jumlah_item']" :keterangan="LaporanCapaian::TAB[$tab]" />
        </div>

        @if ($baris === [])
            <x-kartu>
                <x-kosong pesan="Tidak ada data untuk filter ini." />
            </x-kartu>
        @else
            {{-- Tabel di kiri dan grafik di kanan pada desktop; ditumpuk di mobile (bagian 7.4). --}}
            <div class="grid gap-6 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                <x-kartu judul="Hasil {{ LaporanCapaian::TAB[$tab] }}">
                    <x-tabel :kolom="['Kode', 'Rata-rata Capaian (%)', '% Mahasiswa Lulus (≥ AM)', 'Status']">
                        @foreach ($baris as $satuBaris)
                            @php($rekap = $satuBaris['rekap'])
                            <x-tabel.baris>
                                <x-tabel.sel label="Kode" class="font-medium whitespace-nowrap">
                                    <span title="{{ $satuBaris['keterangan'] }}">{{ $satuBaris['kode'] }}</span>
                                    <span class="block text-xs font-normal text-text-muted max-md:hidden">{{ $rekap['jumlah_mahasiswa'] }} mahasiswa</span>
                                </x-tabel.sel>
                                <x-tabel.sel label="Rata-rata Capaian (%)">{{ $rekap['rata_rata'] !== null ? Angka::format($rekap['rata_rata']) : 'Belum ada nilai' }}</x-tabel.sel>
                                <x-tabel.sel label="% Mahasiswa Lulus (≥ AM)">
                                    @if ($rekap['jumlah_mahasiswa'] === 0)
                                        –
                                    @elseif ($rekap['persen_lulus'] === null)
                                        Ambang belum ditetapkan
                                    @else
                                        {{ Angka::format($rekap['persen_lulus']) }} ({{ $rekap['jumlah_lulus'] }}/{{ $rekap['jumlah_mahasiswa'] }})
                                    @endif
                                </x-tabel.sel>
                                <x-tabel.sel label="Status"><x-status-capaian :rekap="$rekap" /></x-tabel.sel>
                            </x-tabel.baris>
                        @endforeach
                    </x-tabel>
                </x-kartu>

                <x-kartu :judul="$ambangKelulusanAda ? 'Grafik % Mahasiswa Lulus' : 'Grafik Rata-rata Capaian'">
                    <div class="h-60 md:h-80" x-data="grafikLaporan({{ Js::from($dataGrafik) }})">
                        <canvas x-ref="kanvas" role="img" aria-label="Grafik batang {{ strtolower($dataGrafik['labelNilai']) }} per {{ LaporanCapaian::TAB[$tab] }}"></canvas>
                    </div>
                    @if (! $ambangKelulusanAda || $ambang['ak'] === null)
                        <p class="mt-3 text-xs text-text-muted">Ambang belum ditetapkan, jadi batang berwarna biru dan tidak menunjukkan status.</p>
                    @endif
                </x-kartu>
            </div>
        @endif
    @endif
</x-layouts.app>
