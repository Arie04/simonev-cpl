@use('App\Support\Angka')

<x-layouts.app judul="Kelola Rencana Asesmen">
    <div class="space-y-6">
        {{-- Tiga kartu info ber-ikon (bagian 7.6.7). --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-kartu-statistik ikon="heroicon-o-book-open" label="Mata Kuliah" :nilai="$kelas->mataKuliah->kode_mata_kuliah" :keterangan="$kelas->mataKuliah->nama_mata_kuliah" />
            <x-kartu-statistik ikon="heroicon-o-user-group" label="Kelas" :nilai="$kelas->nama_kelas" :keterangan="$kelas->mataKuliah->kurikulum->nama_kurikulum" />
            <x-kartu-statistik ikon="heroicon-o-calendar-days" label="Periode" :nilai="$kelas->periodeAkademik->tahun_akademik" :keterangan="'Semester '.$kelas->periodeAkademik->semester_periode" />
        </div>

        @if ($matriks->peringatan() !== [])
            <x-peringatan>
                <p class="font-semibold">Bobot kelas belum sesuai target</p>
                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                    @foreach ($matriks->peringatan() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </x-peringatan>
        @endif

        <x-kartu>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold">Daftar Asesmen</h2>
                <x-tombol :href="route('dosen.kelas.rencana-asesmen.create', $kelas)">
                    <x-heroicon-o-plus class="size-5" />
                    Tambah Asesmen
                </x-tombol>
            </div>

            @if ($matriks->daftarAsesmen->isEmpty())
                <x-kosong pesan="Belum ada rencana asesmen untuk kelas ini." />
            @else
                <x-tabel :kolom="['Asesmen', 'Metode', 'Bobot (%)', 'Sub-CPMK', 'Nilai Maks', 'Tanggal', 'Aksi']">
                    @foreach ($matriks->daftarAsesmen as $asesmen)
                        <x-tabel.baris>
                            <x-tabel.sel label="Asesmen" class="font-medium">{{ $asesmen->nama_asesmen }}</x-tabel.sel>
                            <x-tabel.sel label="Metode">{{ $asesmen->metodePenilaian->nama_metode }}</x-tabel.sel>
                            <x-tabel.sel label="Bobot (%)">{{ Angka::ringkas($asesmen->bobot_asesmen) }}</x-tabel.sel>
                            <x-tabel.sel label="Sub-CPMK">
                                {{ $asesmen->subCpmk->sortBy('kode_sub_cpmk')->map(fn ($subCpmk) => $subCpmk->kode_sub_cpmk.' ('.Angka::ringkas($subCpmk->pivot->bobot_kontribusi).')')->implode(', ') }}
                            </x-tabel.sel>
                            <x-tabel.sel label="Nilai Maks">{{ Angka::ringkas($asesmen->nilai_maksimum) }}</x-tabel.sel>
                            <x-tabel.sel label="Tanggal" class="whitespace-nowrap">{{ $asesmen->tanggal_asesmen?->translatedFormat('d M Y') ?? '–' }}</x-tabel.sel>
                            <x-tabel.aksi>
                                <x-tombol-ikon ikon="heroicon-o-document-check" :label="'Input nilai '.$asesmen->nama_asesmen" :href="route('dosen.nilai.edit', ['kelas' => $kelas, 'asesmen' => $asesmen])" />
                                <x-tombol-ikon ikon="heroicon-o-pencil-square" :label="'Ubah '.$asesmen->nama_asesmen" :href="route('dosen.kelas.rencana-asesmen.edit', [$kelas, $asesmen])" />
                                <x-tombol-hapus :aksi="route('dosen.kelas.rencana-asesmen.destroy', [$kelas, $asesmen])" :nama="$asesmen->nama_asesmen" />
                            </x-tabel.aksi>
                        </x-tabel.baris>
                    @endforeach
                </x-tabel>
            @endif
        </x-kartu>

        <x-kartu judul="Matriks Bobot Penilaian">
            <p class="mb-4 text-sm text-text-muted">
                Seperti bagian Penilaian RPS APTIKOM: baris = Sub-CPMK, kolom = asesmen, sel = bobot kontribusi
                dalam % nilai akhir mata kuliah. Kolom Target berisi bobot Sub-CPMK di kurikulum; sel kuning berarti
                jumlahnya belum sama dengan target.
            </p>

            @if ($matriks->daftarPemetaan->isEmpty())
                <x-kosong pesan="Mata kuliah ini belum punya CPMK dan Sub-CPMK." />
            @else
                <div class="relative max-h-[70vh] overflow-auto rounded-lg border border-border">
                    <table class="w-max min-w-full border-separate border-spacing-0 text-sm">
                        <thead>
                            <tr>
                                <th scope="col" class="sticky top-0 left-0 z-20 border-r border-b border-border bg-neutral-bg px-3 py-2 text-left text-xs font-semibold text-text-muted">Sub-CPMK</th>
                                @foreach ($matriks->daftarAsesmen as $asesmen)
                                    <th scope="col" class="sticky top-0 z-10 min-w-20 border-b border-border bg-neutral-bg px-2 py-2 text-center text-xs font-semibold">
                                        {{ $asesmen->nama_asesmen }}
                                        <span class="block font-normal text-text-muted">{{ $asesmen->metodePenilaian->kode_metode }}</span>
                                    </th>
                                @endforeach
                                <th scope="col" class="sticky top-0 z-10 border-b border-l border-border bg-neutral-bg px-3 py-2 text-center text-xs font-semibold">Total</th>
                                <th scope="col" class="sticky top-0 z-10 border-b border-border bg-neutral-bg px-3 py-2 text-center text-xs font-semibold">Target</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($matriks->daftarPemetaan as $pemetaan)
                                <tr>
                                    <th scope="rowgroup" colspan="{{ $matriks->daftarAsesmen->count() + 3 }}" class="border-b border-border bg-primary-50 px-3 py-2 text-left text-xs font-semibold text-primary-900">
                                        <span class="sticky left-3">{{ $pemetaan->cpmk->kode_cpmk }} · bobot CPMK {{ Angka::ringkas($pemetaan->bobot_cpmk) }}%</span>
                                    </th>
                                </tr>
                                @foreach ($pemetaan->subCpmk->sortBy('kode_sub_cpmk') as $subCpmk)
                                    @php($total = $matriks->totalSubCpmk($subCpmk))
                                    <tr>
                                        <th scope="row" class="sticky left-0 z-10 w-40 max-w-40 border-r border-b border-border bg-surface px-3 py-2 text-left font-normal sm:w-56 sm:max-w-56">
                                            <span class="font-medium">{{ $subCpmk->kode_sub_cpmk }}</span>
                                            <span class="line-clamp-2 text-xs text-text-muted" title="{{ $subCpmk->deskripsi_sub_cpmk }}">{{ $subCpmk->deskripsi_sub_cpmk }}</span>
                                        </th>
                                        @foreach ($matriks->daftarAsesmen as $asesmen)
                                            <td class="border-b border-border px-2 py-2 text-center">{{ Angka::ringkas($matriks->bobot($asesmen, $subCpmk)) }}</td>
                                        @endforeach
                                        <td @class([
                                            'border-b border-l border-border px-3 py-2 text-center font-semibold',
                                            'bg-warning-bg text-warning' => ! Angka::sama($total, $subCpmk->bobot_sub_cpmk),
                                        ])>{{ Angka::ringkas($total) }}</td>
                                        <td class="border-b border-border px-3 py-2 text-center text-text-muted">{{ Angka::ringkas($subCpmk->bobot_sub_cpmk) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold">
                                <th scope="row" class="sticky left-0 z-10 border-r border-border bg-neutral-bg px-3 py-2 text-left text-xs">Total per asesmen</th>
                                @foreach ($matriks->daftarAsesmen as $asesmen)
                                    <td class="bg-neutral-bg px-2 py-2 text-center">{{ Angka::ringkas($matriks->totalAsesmen($asesmen)) }}</td>
                                @endforeach
                                <td @class([
                                    'border-l border-border px-3 py-2 text-center',
                                    'bg-neutral-bg' => Angka::sama($matriks->totalBobotAsesmen(), 100),
                                    'bg-warning-bg text-warning' => ! Angka::sama($matriks->totalBobotAsesmen(), 100),
                                ])>{{ Angka::ringkas($matriks->totalBobotAsesmen()) }}</td>
                                <td class="bg-neutral-bg px-3 py-2 text-center text-text-muted">100</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-kartu>

        <div class="flex justify-end">
            <x-tombol varian="outline" :href="route('dosen.rencana-asesmen')">Kembali ke Daftar Kelas</x-tombol>
        </div>
    </div>
</x-layouts.app>
