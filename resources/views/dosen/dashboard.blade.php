<x-layouts.app judul="Dashboard">
    <p class="-mt-3 mb-6 text-text-muted">
        Selamat datang, {{ auth()->user()->nama }}.
        @if ($periodeAktif)
            Kelas yang Anda ampu pada periode {{ $periodeAktif->nama() }}:
        @endif
    </p>

    @if ($daftarKelas->isEmpty())
        <x-kartu>
            <x-kosong :pesan="$periodeAktif ? 'Anda belum mengampu kelas pada periode '.$periodeAktif->nama().'.' : 'Belum ada kelas pada periode mana pun.'" />
        </x-kartu>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($daftarKelas as $kelas)
                <x-kartu>
                    <p class="text-sm text-text-muted">{{ $kelas->mataKuliah->kode_mata_kuliah }} · {{ $kelas->mataKuliah->kurikulum->nama_kurikulum }}</p>
                    <h2 class="mt-1 text-lg font-semibold">{{ $kelas->mataKuliah->nama_mata_kuliah }}</h2>
                    <dl class="mt-4 grid grid-cols-3 gap-2 text-sm">
                        <div>
                            <dt class="text-text-muted">Kelas</dt>
                            <dd class="font-semibold">{{ $kelas->nama_kelas }}</dd>
                        </div>
                        <div>
                            <dt class="text-text-muted">Peserta</dt>
                            <dd class="font-semibold">{{ $kelas->peserta_kelas_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-text-muted">Asesmen</dt>
                            <dd class="font-semibold">{{ $kelas->rencana_asesmen_count }}</dd>
                        </div>
                    </dl>
                    <p class="mt-2 text-xs text-text-muted">Periode {{ $kelas->periodeAkademik->nama() }}</p>
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-border pt-4">
                        <x-tombol varian="outline" :href="route('dosen.kelas.rencana-asesmen.index', $kelas)">
                            <x-heroicon-o-clipboard-document-list class="size-5" />
                            Rencana Asesmen
                        </x-tombol>
                    </div>
                </x-kartu>
            @endforeach
        </div>
    @endif
</x-layouts.app>
