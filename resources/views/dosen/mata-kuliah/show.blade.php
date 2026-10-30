<x-layouts.app :judul="$mataKuliah->kode_mata_kuliah.' '.$mataKuliah->nama_mata_kuliah">
    <div class="space-y-6">
        <x-kartu judul="Mata Kuliah">
            <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-[12rem_1fr]">
                <dt class="text-text-muted">Kurikulum</dt>
                <dd>{{ $mataKuliah->kurikulum->nama_kurikulum }}</dd>
                <dt class="text-text-muted">Semester / SKS</dt>
                <dd>Semester {{ $mataKuliah->semester }} · {{ $mataKuliah->jumlah_sks }} SKS</dd>
                <dt class="text-text-muted">Jenis</dt>
                <dd>{{ $mataKuliah->jenis_mata_kuliah }}</dd>
            </dl>
        </x-kartu>

        <x-kartu judul="CPL yang Didukung">
            @forelse ($daftarCpl as $cpl)
                <div class="border-b border-border py-3 first:pt-0 last:border-b-0 last:pb-0">
                    <p class="font-semibold">{{ $cpl->kode_cpl }} <span class="font-normal text-text-muted">· {{ $cpl->kategori_cpl }}</span></p>
                    <p class="text-sm">{{ $cpl->deskripsi_cpl }}</p>
                </div>
            @empty
                <x-kosong pesan="Mata kuliah ini belum dipetakan ke CPMK mana pun." />
            @endforelse
        </x-kartu>

        <x-kartu judul="CPMK dan Sub-CPMK">
            <p class="mb-4 text-sm text-text-muted">Bobot dalam persen nilai akhir mata kuliah, sesuai kurikulum (target untuk rencana asesmen kelas).</p>
            <x-ringkasan-bobot-mk :mata-kuliah="$mataKuliah" />
        </x-kartu>

        <div class="flex justify-end">
            <x-tombol varian="outline" :href="route('dosen.mata-kuliah.index')">Kembali</x-tombol>
        </div>
    </div>
</x-layouts.app>
