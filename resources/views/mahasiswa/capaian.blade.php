@use('App\Support\Angka')

<x-layouts.app judul="Capaian Pembelajaran">
    <div class="space-y-6">
        {{-- Kartu identitas dan pilihan periode/mata kuliah (bagian 7.6.10). --}}
        <x-kartu>
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-accent text-lg font-bold text-on-accent">{{ auth()->user()->inisial() }}</span>
                    <div class="min-w-0">
                        <p class="text-lg font-semibold break-words">{{ $mahasiswa->nama }}</p>
                        <p class="text-sm text-text-muted">NPM {{ $mahasiswa->nim }} · Angkatan {{ $mahasiswa->angkatan }}</p>
                    </div>
                </div>

                @if ($pilihanPeriode !== [])
                    <form method="GET" class="grid gap-4 sm:grid-cols-2 lg:w-[36rem]">
                        <x-select name="periode" label="Periode" :pilihan="$pilihanPeriode" :value="$periode?->periode_akademik_id" x-on:change="$el.form.submit()" />
                        <x-select name="mata_kuliah" label="Mata Kuliah" :pilihan="$pilihanMataKuliah" :value="$peserta?->kelasKuliah->mata_kuliah_id" x-on:change="$el.form.submit()" />
                        <noscript><x-tombol varian="outline" type="submit">Tampilkan</x-tombol></noscript>
                    </form>
                @endif
            </div>
        </x-kartu>

        <p class="text-sm text-text-muted">
            Ambang Batas Kelulusan Mahasiswa (AM):
            <strong class="text-text">{{ $ambang['am'] !== null ? Angka::format($ambang['am']) : 'Belum ditetapkan' }}</strong>.
            Anda dinyatakan Lulus pada suatu capaian jika nilainya ≥ AM.
        </p>

        @if ($mataKuliah === null)
            <x-kartu>
                <x-kosong pesan="Anda belum terdaftar di kelas mana pun." />
            </x-kartu>
        @else
            {{-- Ringkasan: tiga kartu angka besar. --}}
            <section aria-labelledby="judul-ringkasan">
                <h2 id="judul-ringkasan" class="mb-3 text-lg font-semibold">Ringkasan</h2>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
                    <x-kartu-statistik
                        ikon="heroicon-o-list-bullet"
                        label="Rata-rata Sub-CPMK"
                        :nilai="$mataKuliah['ringkasan']['sub_cpmk'] !== null ? Angka::format($mataKuliah['ringkasan']['sub_cpmk']) : '–'"
                        :keterangan="$mataKuliah['kelas']->mataKuliah->kode_mata_kuliah"
                    />
                    <x-kartu-statistik
                        ikon="heroicon-o-clipboard-document-check"
                        label="Rata-rata CPMK"
                        :nilai="$mataKuliah['ringkasan']['cpmk'] !== null ? Angka::format($mataKuliah['ringkasan']['cpmk']) : '–'"
                        :keterangan="$mataKuliah['kelas']->mataKuliah->kode_mata_kuliah"
                    />
                    <x-kartu-statistik
                        ikon="heroicon-o-trophy"
                        label="Rata-rata CPL Kumulatif"
                        :nilai="$mataKuliah['ringkasan']['cpl_kumulatif'] !== null ? Angka::format($mataKuliah['ringkasan']['cpl_kumulatif']) : '–'"
                        :keterangan="$mataKuliah['kelas']->mataKuliah->kurikulum->nama_kurikulum"
                    />
                </div>
            </section>

            <x-kartu :judul="'Detail Nilai Asesmen · '.$mataKuliah['kelas']->nama()">
                @if ($mataKuliah['nilaiAsesmen']->isEmpty())
                    <x-kosong pesan="Dosen belum membuat rencana asesmen untuk kelas ini." />
                @else
                    <x-tabel :kolom="['No.', 'Asesmen', 'Nilai']">
                        @foreach ($mataKuliah['nilaiAsesmen'] as $nomor => $nilai)
                            <x-tabel.baris>
                                <x-tabel.sel label="No.">{{ $nomor + 1 }}</x-tabel.sel>
                                {{-- Isi sel dibungkus satu elemen supaya tetap utuh saat baris menjadi kartu di mobile. --}}
                                <x-tabel.sel label="Asesmen">
                                    <span>
                                        <span class="font-medium">{{ $nilai['nama'] }}</span>
                                        <span class="block text-xs text-text-muted">{{ $nilai['metode'] }}</span>
                                    </span>
                                </x-tabel.sel>
                                <x-tabel.sel label="Nilai" class="whitespace-nowrap">
                                    @if ($nilai['nilai'] === null)
                                        <x-badge ikon="heroicon-o-minus">Belum ada nilai</x-badge>
                                    @else
                                        <span>
                                            <span class="font-semibold">{{ Angka::ringkas($nilai['nilai']) }}</span>
                                            <span class="text-xs text-text-muted">dari {{ Angka::ringkas($nilai['nilai_maksimum']) }}</span>
                                        </span>
                                    @endif
                                </x-tabel.sel>
                            </x-tabel.baris>
                        @endforeach
                    </x-tabel>
                @endif
            </x-kartu>

            <x-kartu judul="Capaian Sub-CPMK">
                @include('mahasiswa._tabel-capaian', ['baris' => $mataKuliah['subCpmk']])
            </x-kartu>

            <x-kartu judul="Capaian CPMK">
                @include('mahasiswa._tabel-capaian', ['baris' => $mataKuliah['cpmk']])
            </x-kartu>

            <x-kartu judul="Kontribusi Mata Kuliah ke CPL">
                <p class="mb-4 text-sm text-text-muted">Capaian CPL yang dihitung dari mata kuliah ini saja.</p>
                @include('mahasiswa._tabel-capaian', ['baris' => $mataKuliah['cpl']])
            </x-kartu>
        @endif

        {{-- CPL kumulatif dari semua MK yang diikuti, dipisah per kurikulum (bagian 5.4). --}}
        @foreach ($cplKumulatif as $kelompok)
            <x-kartu :judul="'CPL Kumulatif · '.$kelompok['kurikulum']->nama_kurikulum">
                <p class="mb-4 text-sm text-text-muted">
                    Dihitung dari semua mata kuliah {{ $kelompok['kurikulum']->nama_kurikulum }} yang sudah Anda ikuti.
                    Untuk mata kuliah yang diulang, dipakai kelas terbaru yang sudah punya nilai.
                </p>
                @include('mahasiswa._tabel-capaian', ['baris' => $kelompok['cpl']])
            </x-kartu>
        @endforeach
    </div>
</x-layouts.app>
