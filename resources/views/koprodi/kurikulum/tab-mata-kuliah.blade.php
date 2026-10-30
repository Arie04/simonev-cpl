@if ($daftarMataKuliah->isEmpty())
    <x-kosong pesan="Belum ada data mata kuliah." />
@else
    <p class="mb-4 text-sm text-text-muted">Pilih mata kuliah untuk melihat ringkasan bobot CPMK dan Sub-CPMK-nya.</p>
    <div class="space-y-3">
        @foreach ($daftarMataKuliah as $mataKuliah)
            {{-- <details> bisa dibuka-tutup tanpa JavaScript. --}}
            <details class="group rounded-lg border border-border">
                <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                    <span>
                        <span class="font-semibold">{{ $mataKuliah->kode_mata_kuliah }}</span>
                        {{ $mataKuliah->nama_mata_kuliah }}
                        <span class="block text-xs text-text-muted">Semester {{ $mataKuliah->semester }} · {{ $mataKuliah->jumlah_sks }} SKS · {{ $mataKuliah->jenis_mata_kuliah }}</span>
                    </span>
                    <x-heroicon-o-chevron-down class="size-5 shrink-0 text-text-muted transition-transform group-open:rotate-180" />
                </summary>
                <div class="border-t border-border p-4">
                    <x-ringkasan-bobot-mk :mata-kuliah="$mataKuliah" />
                </div>
            </details>
        @endforeach
    </div>
@endif
