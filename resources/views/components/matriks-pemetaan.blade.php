@props(['matriks', 'bisaUbah' => false])

@use('App\Services\MatriksPemetaan')
@use('App\Support\Angka')

@php
    // Pasangan yang tersimpan. Jika form baru saja gagal divalidasi, pilihan terakhir pengguna
    // yang ditampilkan supaya isiannya tidak hilang.
    $terhubung = $matriks->terhubung();
    $pakaiIsianLama = $bisaUbah && session()->hasOldInput('jenis');
    $kunciDipilih = $pakaiIsianLama ? (array) old('terhubung', []) : array_keys($terhubung);
    $totalPerMataKuliah = $matriks->jenis === 'cpmk-mk' ? $matriks->totalBobotPerMataKuliah() : [];
@endphp

@if ($matriks->daftarBaris->isEmpty() || $matriks->daftarKolom->isEmpty())
    <x-kosong :pesan="'Belum ada data '.$matriks->judulBaris().' atau '.$matriks->judulKolom().' di kurikulum ini.'" />
@else
    {{-- Matriks bisa digeser horizontal di dalam kartunya; kolom pertama dan baris judul menempel (bagian 7.4). --}}
    <div class="relative max-h-[70vh] overflow-auto rounded-lg border border-border">
        <table class="w-max min-w-full border-separate border-spacing-0 text-sm">
            <thead>
                <tr>
                    <th scope="col" class="sticky top-0 left-0 z-20 border-r border-b border-border bg-neutral-bg px-3 py-2 text-left text-xs font-semibold text-text-muted">
                        {{ $matriks->judulBaris() }} \ {{ $matriks->judulKolom() }}
                    </th>
                    @foreach ($matriks->daftarKolom as $kolom)
                        <th scope="col" class="sticky top-0 z-10 min-w-16 border-b border-border bg-neutral-bg px-2 py-2 text-center text-xs font-semibold" title="{{ $matriks->keterangan($kolom) }}">
                            {{ $matriks->kode($kolom) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($matriks->daftarBaris as $baris)
                    <tr>
                        <th scope="row" class="sticky left-0 z-10 w-44 max-w-44 border-r border-b border-border bg-surface px-3 py-2 text-left align-top font-normal sm:w-60 sm:max-w-60">
                            <span class="font-semibold">{{ $matriks->kode($baris) }}</span>
                            <span class="line-clamp-2 text-xs text-text-muted" title="{{ $matriks->keterangan($baris) }}">{{ $matriks->keterangan($baris) }}</span>
                        </th>
                        @foreach ($matriks->daftarKolom as $kolom)
                            @php
                                $kunci = MatriksPemetaan::kunci($baris->getKey(), $kolom->getKey());
                                $dipilih = in_array($kunci, $kunciDipilih, true);
                                $namaSel = $matriks->kode($baris).' – '.$matriks->kode($kolom);
                            @endphp
                            <td class="border-b border-border px-1.5 py-1 text-center align-middle">
                                @if ($bisaUbah)
                                    <div x-data="{ terhubung: @js($dipilih) }" class="flex flex-col items-center">
                                        {{-- Lingkaran yang bisa diklik; area sentuhnya 44 × 44 px (bagian 7.4). --}}
                                        <label class="flex size-11 cursor-pointer items-center justify-center rounded-full hover:bg-primary-50">
                                            <input
                                                type="checkbox"
                                                name="terhubung[]"
                                                value="{{ $kunci }}"
                                                x-model="terhubung"
                                                @checked($dipilih)
                                                aria-label="{{ $namaSel }}"
                                                class="size-6 cursor-pointer appearance-none rounded-full border-2 border-text-muted/40 bg-surface checked:border-primary-700 checked:bg-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700"
                                            >
                                        </label>
                                        @if ($matriks->kolomBobot() !== null)
                                            {{-- Isian bobot hanya aktif (dan ikut terkirim) untuk sel yang terhubung. --}}
                                            <input
                                                type="text"
                                                inputmode="decimal"
                                                name="bobot[{{ $kunci }}]"
                                                value="{{ old('bobot.'.$kunci, Angka::ringkas($terhubung[$kunci] ?? null)) }}"
                                                x-show="terhubung"
                                                x-bind:disabled="! terhubung"
                                                @disabled(! $dipilih)
                                                @if (! $dipilih) style="display: none" @endif
                                                aria-label="Bobot {{ $namaSel }}"
                                                @class([
                                                    'mb-1 w-16 rounded-md border px-1 py-1 text-center text-base sm:text-sm',
                                                    'border-danger bg-danger-bg' => $errors->has('bobot.'.$kunci),
                                                    'border-border' => ! $errors->has('bobot.'.$kunci),
                                                ])
                                            >
                                        @endif
                                    </div>
                                @elseif ($dipilih)
                                    <span class="inline-flex size-6 rounded-full bg-primary-700" aria-hidden="true"></span>
                                    <span class="sr-only">{{ $namaSel }} terhubung</span>
                                    @if ($matriks->kolomBobot() !== null)
                                        <span class="block text-xs font-medium">{{ Angka::ringkas($terhubung[$kunci]) }}</span>
                                    @endif
                                @else
                                    <span class="inline-flex size-6 rounded-full border-2 border-border" aria-hidden="true"></span>
                                    <span class="sr-only">{{ $namaSel }} tidak terhubung</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
            @if ($totalPerMataKuliah !== [])
                {{-- Σ bobot CPMK per MK seharusnya 100 (bagian 6); hanya peringatan, tidak memblokir. --}}
                <tfoot>
                    <tr>
                        <th scope="row" class="sticky bottom-0 left-0 z-20 border-r border-border bg-neutral-bg px-3 py-2 text-left text-xs font-semibold">Total bobot (target 100)</th>
                        @foreach ($matriks->daftarKolom as $kolom)
                            @php($total = $totalPerMataKuliah[$kolom->getKey()])
                            <td @class([
                                'sticky bottom-0 z-10 border-border px-2 py-2 text-center text-xs font-semibold',
                                'bg-neutral-bg' => abs($total - 100) < 0.005,
                                'bg-warning-bg text-warning' => abs($total - 100) >= 0.005,
                            ]) @if (abs($total - 100) >= 0.005) title="Total bobot CPMK mata kuliah ini belum 100" @endif>
                                {{ Angka::ringkas($total) }}
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
@endif
