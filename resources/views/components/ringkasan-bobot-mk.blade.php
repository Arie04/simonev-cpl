@props(['mataKuliah'])

@use('App\Support\Angka')

@php
    // Butuh relasi pemetaanCpmkMk.cpmk.cpl dan pemetaanCpmkMk.subCpmk yang sudah dimuat.
    $daftarPemetaan = $mataKuliah->pemetaanCpmkMk->sortBy(fn ($pemetaan) => $pemetaan->cpmk->kode_cpmk);
    $totalBobotCpmk = $mataKuliah->totalBobotCpmk();
@endphp

{{-- Ringkasan bobot CPMK dan Sub-CPMK satu mata kuliah, mirip Tabel 19 APTIKOM (bagian 7.6.4).
     Bobot yang tidak sesuai target hanya diberi peringatan, tidak memblokir (bagian 6). --}}
@if ($daftarPemetaan->isEmpty())
    <x-kosong pesan="Belum ada CPMK yang dipetakan ke mata kuliah ini." />
@else
    <div class="overflow-x-auto rounded-lg border border-border">
        <table class="w-full text-left text-sm">
            <thead class="bg-neutral-bg text-xs font-semibold text-text-muted">
                <tr>
                    <th scope="col" class="px-4 py-3">Kode</th>
                    <th scope="col" class="hidden px-4 py-3 sm:table-cell">Deskripsi</th>
                    <th scope="col" class="px-4 py-3 text-right whitespace-nowrap">Bobot (%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($daftarPemetaan as $pemetaan)
                    <tr class="border-t border-border bg-primary-50/50">
                        <td class="px-4 py-2.5 align-top font-semibold">
                            {{ $pemetaan->cpmk->kode_cpmk }}
                            <span class="block text-xs font-normal text-text-muted">{{ $pemetaan->cpmk->cpl->kode_cpl }}</span>
                            {{-- Di layar kecil deskripsi pindah ke bawah kode supaya tabel tidak perlu digeser. --}}
                            <span class="mt-1 block text-xs font-normal sm:hidden">{{ $pemetaan->cpmk->deskripsi_cpmk }}</span>
                        </td>
                        <td class="hidden px-4 py-2.5 align-top sm:table-cell">{{ $pemetaan->cpmk->deskripsi_cpmk }}</td>
                        <td class="px-4 py-2.5 text-right align-top font-semibold">{{ Angka::ringkas($pemetaan->bobot_cpmk) }}</td>
                    </tr>
                    @foreach ($pemetaan->subCpmk->sortBy('kode_sub_cpmk') as $subCpmk)
                        <tr class="border-t border-border">
                            <td class="py-2 pr-4 pl-8 align-top">
                                {{ $subCpmk->kode_sub_cpmk }}
                                <span class="mt-1 block text-xs text-text-muted sm:hidden">{{ $subCpmk->deskripsi_sub_cpmk }}</span>
                            </td>
                            <td class="hidden px-4 py-2 align-top text-text-muted sm:table-cell">{{ $subCpmk->deskripsi_sub_cpmk }}</td>
                            <td class="px-4 py-2 text-right align-top">{{ Angka::ringkas($subCpmk->bobot_sub_cpmk) }}</td>
                        </tr>
                    @endforeach
                    @unless (Angka::sama($pemetaan->totalBobotSubCpmk(), $pemetaan->bobot_cpmk))
                        <tr class="border-t border-border bg-warning-bg text-warning">
                            <td colspan="3" class="px-4 py-2 text-xs">
                                <x-heroicon-o-exclamation-triangle class="mr-1 inline size-4 align-text-bottom" />
                                Jumlah bobot Sub-CPMK {{ $pemetaan->cpmk->kode_cpmk }} adalah {{ Angka::ringkas($pemetaan->totalBobotSubCpmk()) }}, belum sama dengan bobot CPMK {{ Angka::ringkas($pemetaan->bobot_cpmk) }}.
                            </td>
                        </tr>
                    @endunless
                @endforeach
            </tbody>
            <tfoot>
                <tr @class([
                    'border-t border-border font-semibold',
                    'bg-neutral-bg' => Angka::sama($totalBobotCpmk, 100),
                    'bg-warning-bg text-warning' => ! Angka::sama($totalBobotCpmk, 100),
                ])>
                    <td class="px-4 py-3 sm:hidden">Total bobot CPMK (target 100)</td>
                    <td class="hidden px-4 py-3 sm:table-cell" colspan="2">Total bobot CPMK (target 100)</td>
                    <td class="px-4 py-3 text-right">{{ Angka::ringkas($totalBobotCpmk) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @unless (Angka::sama($totalBobotCpmk, 100))
        <x-peringatan class="mt-4">
            Total bobot CPMK mata kuliah ini {{ Angka::ringkas($totalBobotCpmk) }}, seharusnya 100. Atur bobotnya di matriks Pemetaan CPMK–Mata Kuliah.
        </x-peringatan>
    @endunless
@endif
