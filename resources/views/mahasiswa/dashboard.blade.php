@use('App\Support\Angka')

@php
    // CPL yang terpilih saat halaman dibuka: CPL pertama yang sudah punya nilai.
    $terpilihAwal = $daftarCpl->search(fn (array $cpl) => $cpl['capaian'] !== null);
    $terpilihAwal = $terpilihAwal === false ? 0 : $terpilihAwal;
    // Data untuk grafik (resources/js/app.js). Angka dibulatkan 2 desimal, sama dengan angka di tabel.
    $bulatkan = fn (?float $nilai) => $nilai === null ? null : round($nilai, 2);
    $dataGrafik = [
        'label' => $daftarCpl->pluck('kode'),
        'deskripsi' => $daftarCpl->pluck('deskripsi'),
        'nilai' => $daftarCpl->map(fn (array $cpl) => $bulatkan($cpl['capaian'])),
        'status' => $daftarCpl->map(fn (array $cpl) => match (true) {
            $cpl['capaian'] === null => 'kosong',
            $cpl['lulus'] === true => 'lulus',
            $cpl['lulus'] === false => 'belum',
            default => 'netral',
        }),
        'ambang' => $ambang['am'],
        'periode' => $perkembangan['periode'] ?? [],
        'perkembangan' => collect($perkembangan['nilai'] ?? [])->map(fn (array $baris) => array_map($bulatkan, $baris)),
        'terpilih' => $terpilihAwal,
    ];
@endphp

<x-layouts.app judul="Dashboard">
    <div class="-mt-3 mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <p class="text-text-muted">Selamat datang, {{ $mahasiswa->nama }}. Ringkasan capaian pembelajaran Anda.</p>
        {{-- Pilihan kurikulum hanya muncul untuk mahasiswa masa transisi yang mengikuti MK dari dua kurikulum (bagian 5.4). --}}
        @if (count($pilihanKurikulum) > 1)
            <form method="GET" class="md:w-72">
                <x-select name="kurikulum" label="Kurikulum" :pilihan="$pilihanKurikulum" :value="$kurikulum?->kurikulum_id" x-on:change="$el.form.submit()" />
                <noscript><x-tombol varian="outline" type="submit" class="mt-2">Tampilkan</x-tombol></noscript>
            </form>
        @endif
    </div>

    @if ($kurikulum === null)
        <x-kartu>
            <x-kosong pesan="Anda belum terdaftar di kelas mana pun." />
        </x-kartu>
    @else
        {{-- Kartu ringkasan: 2 kolom di mobile, 3 kolom di desktop (bagian 7.4). --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
            <x-kartu-statistik
                ikon="heroicon-o-trophy"
                label="Rata-rata CPL"
                :nilai="Angka::format($ringkasan['rata_rata'])"
                keterangan="Kumulatif semua periode"
            />
            <x-kartu-statistik
                ikon="heroicon-o-check-badge"
                label="CPL Lulus"
                :nilai="$ringkasan['jumlah_lulus'] === null ? '–' : $ringkasan['jumlah_lulus'].' dari '.$ringkasan['jumlah_cpl']"
                :keterangan="$ambang['am'] === null ? 'Ambang belum ditetapkan' : 'Capaian ≥ '.Angka::format($ambang['am'])"
            />
            <x-kartu-statistik
                ikon="heroicon-o-book-open"
                label="Mata Kuliah Diikuti"
                :nilai="$ringkasan['jumlah_mata_kuliah']"
                :keterangan="$ringkasan['jumlah_periode'].' periode'"
                class="col-span-2 lg:col-span-1"
            />
        </div>

        {{-- Grafik dan rincian berbagi satu komponen Alpine supaya CPL terpilih sama di semua bagian. --}}
        <div x-data="dashboardMahasiswa({{ Js::from($dataGrafik) }})" class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <x-kartu judul="Capaian CPL Kumulatif">
                <p class="mb-4 text-xs text-text-muted">
                    CPL kumulatif semua periode, {{ $kurikulum->nama_kurikulum }}.
                    @if ($ambang['am'] !== null)
                        Garis putus-putus adalah Ambang Batas Kelulusan Mahasiswa ({{ Angka::format($ambang['am']) }}).
                    @endif
                    Tanda – berarti belum ada nilai. Klik kolom CPL di grafik atau tombol kode CPL di bawahnya untuk melihat rinciannya.
                </p>

                @if ($daftarCpl->isEmpty())
                    <x-kosong pesan="Kurikulum ini belum punya CPL." />
                @else
                    <div class="h-60 md:h-80">
                        <canvas
                            x-ref="kanvasCpl"
                            role="img"
                            aria-label="Grafik batang capaian CPL kumulatif: {{ $daftarCpl->map(fn ($cpl) => $cpl['kode'].' '.($cpl['capaian'] === null ? 'belum ada nilai' : Angka::format($cpl['capaian'])))->join(', ') }}"
                        ></canvas>
                    </div>

                    {{-- Tombol kode CPL: cara memilih CPL dengan keyboard atau sentuhan, selain mengeklik batang. --}}
                    <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="Pilih CPL">
                        @foreach ($daftarCpl as $indeks => $cpl)
                            <button
                                type="button"
                                x-on:click="pilih({{ $indeks }})"
                                x-bind:aria-pressed="terpilih === {{ $indeks }}"
                                x-bind:class="terpilih === {{ $indeks }} ? 'border-primary-700 bg-primary-50 text-primary-900' : 'border-border text-text-muted hover:bg-bg hover:text-text'"
                                class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border px-3 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"
                            >{{ $cpl['kode'] }}</button>
                        @endforeach
                    </div>
                @endif
            </x-kartu>

            @if ($daftarCpl->isNotEmpty())
                {{-- Rincian CPL terpilih: CPMK penyusun beserta capaian dan bobotnya (rumus 4 bagian 5.2). --}}
                <x-kartu aria-live="polite">
                    @foreach ($daftarCpl as $indeks => $cpl)
                        <div x-show="terpilih === {{ $indeks }}" @if ($indeks !== $terpilihAwal) x-cloak @endif>
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h2 class="text-lg font-semibold">Rincian {{ $cpl['kode'] }}</h2>
                                <x-status-lulus :capaian="$cpl['capaian']" :lulus="$cpl['lulus']" />
                            </div>
                            <p class="mt-1 text-sm text-text-muted">{{ $cpl['deskripsi'] }}</p>
                            <p class="mt-4 text-sm text-text-muted">Capaian CPL kumulatif</p>
                            <p class="text-[1.75rem] font-bold">{{ $cpl['capaian'] === null ? 'Belum ada nilai' : Angka::format($cpl['capaian']) }}</p>

                            @if ($cpl['penyusun'] === [])
                                <p class="mt-4 text-sm text-text-muted">Belum ada mata kuliah yang Anda ikuti yang sudah menilai CPL ini.</p>
                            @else
                                <h3 class="mt-5 mb-3 text-sm font-semibold">Dihitung dari CPMK berikut</h3>
                                <x-tabel :kolom="['CPMK · Mata Kuliah', 'Capaian', 'Bobot']">
                                    @foreach ($cpl['penyusun'] as $penyusun)
                                        <x-tabel.baris>
                                            <x-tabel.sel label="CPMK · Mata Kuliah">
                                                <span>
                                                    <span class="font-medium whitespace-nowrap">{{ $penyusun['kode_cpmk'] }} · {{ $penyusun['kode_mata_kuliah'] }}</span>
                                                    <span class="block text-xs text-text-muted">{{ $penyusun['periode'] }}</span>
                                                </span>
                                            </x-tabel.sel>
                                            <x-tabel.sel label="Capaian" class="whitespace-nowrap">{{ Angka::format($penyusun['capaian']) }}</x-tabel.sel>
                                            <x-tabel.sel label="Bobot" class="whitespace-nowrap">{{ Angka::ringkas($penyusun['bobot']) }}</x-tabel.sel>
                                        </x-tabel.baris>
                                    @endforeach
                                </x-tabel>
                                <p class="mt-3 text-xs text-text-muted">
                                    Capaian CPL adalah rata-rata capaian CPMK di atas yang ditimbang dengan bobotnya (% nilai akhir mata kuliah yang sudah dinilai).
                                </p>
                            @endif
                        </div>
                    @endforeach
                </x-kartu>

                {{-- Perkembangan CPL kumulatif per periode (bagian 5.3: dipantau setiap tingkat sampai lulus). --}}
                <x-kartu judul="Perkembangan CPL per Periode" class="xl:col-span-2">
                    @if ($perkembangan === null)
                        {{-- Kelas di kurikulum lain tidak ikut dihitung, jadi sebutkan pilihan kurikulumnya jika ada. --}}
                        <x-kosong :pesan="'Grafik perkembangan tampil jika kelas Anda di kurikulum ini tersebar di lebih dari satu periode.'
                            .(count($pilihanKurikulum) > 1 ? ' Kurikulum lain bisa dipilih di bagian atas halaman.' : '')" />
                    @else
                        <p class="mb-4 text-xs text-text-muted">
                            Capaian CPL kumulatif pada akhir setiap periode. Garis biru adalah
                            <span x-text="kodeTerpilih">{{ $daftarCpl[$terpilihAwal]['kode'] }}</span>;
                            garis abu adalah CPL lain. Klik titik pada garis untuk memilih CPL.
                        </p>
                        <div class="h-60 md:h-80">
                            <canvas x-ref="kanvasPerkembangan" role="img" aria-label="Grafik garis perkembangan capaian CPL per periode. Angkanya ada di tabel di bawah grafik."></canvas>
                        </div>

                        {{-- Tabel yang sama dengan grafik, supaya angka bisa dibaca tanpa mengarahkan kursor.
                             Kolom CPL menempel saat tabel digeser di layar kecil (bagian 7.4). --}}
                        <div class="mt-4 overflow-x-auto rounded-lg border border-border">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-neutral-bg text-xs font-semibold text-text-muted">
                                    <tr>
                                        <th scope="col" class="sticky left-0 z-10 bg-neutral-bg px-4 py-3">CPL</th>
                                        @foreach ($perkembangan['periode'] as $namaPeriode)
                                            <th scope="col" class="px-4 py-3 text-right whitespace-nowrap">{{ $namaPeriode }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($daftarCpl as $indeks => $cpl)
                                        <tr class="border-t border-border" x-bind:class="terpilih === {{ $indeks }} && 'bg-primary-50'">
                                            <th scope="row" class="sticky left-0 z-10 px-4 py-3 font-medium" x-bind:class="terpilih === {{ $indeks }} ? 'bg-primary-50' : 'bg-surface'">{{ $cpl['kode'] }}</th>
                                            @foreach ($perkembangan['nilai'][$indeks] as $nilai)
                                                <td class="px-4 py-3 text-right whitespace-nowrap">{{ Angka::format($nilai) }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-kartu>
            @endif
        </div>

        <x-kartu judul="Mata Kuliah yang Diikuti" class="mt-6">
            <x-tabel :kolom="['Mata Kuliah', 'Periode', 'Asesmen Dinilai', 'Aksi']">
                @foreach ($daftarMataKuliah as $baris)
                    <x-tabel.baris>
                        <x-tabel.sel label="Mata Kuliah">
                            <span>
                                <span class="font-medium">{{ $baris['kelas']->mataKuliah->kode_mata_kuliah }}</span>
                                <span class="block text-xs text-text-muted">{{ $baris['kelas']->mataKuliah->nama_mata_kuliah }} · Kelas {{ $baris['kelas']->nama_kelas }}</span>
                            </span>
                        </x-tabel.sel>
                        <x-tabel.sel label="Periode" class="whitespace-nowrap">{{ $baris['kelas']->periodeAkademik->nama() }}</x-tabel.sel>
                        <x-tabel.sel label="Asesmen Dinilai" class="whitespace-nowrap">{{ $baris['jumlah_dinilai'] }} dari {{ $baris['jumlah_asesmen'] }}</x-tabel.sel>
                        <x-tabel.aksi>
                            <x-tombol-ikon
                                ikon="heroicon-o-eye"
                                :label="'Lihat capaian '.$baris['kelas']->mataKuliah->kode_mata_kuliah.' '.$baris['kelas']->periodeAkademik->nama()"
                                :href="route('mahasiswa.capaian', ['periode' => $baris['kelas']->periode_akademik_id, 'mata_kuliah' => $baris['kelas']->mata_kuliah_id])"
                            />
                        </x-tabel.aksi>
                    </x-tabel.baris>
                @endforeach
            </x-tabel>
        </x-kartu>
    @endif
</x-layouts.app>
