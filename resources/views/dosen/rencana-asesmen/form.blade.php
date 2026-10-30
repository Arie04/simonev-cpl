@use('App\Support\Angka')

@php
    // Status awal tiap Sub-CPMK untuk Alpine: dipilih atau tidak, dan bobot kontribusinya.
    // Jika validasi gagal, isian terakhir pengguna yang dipakai.
    $kontribusiLama = session()->hasOldInput('nama_asesmen') ? (array) old('kontribusi', []) : null;
    $statusSubCpmk = $daftarSubCpmk->mapWithKeys(function ($subCpmk) use ($kontribusiLama, $kontribusiTersimpan) {
        $id = $subCpmk->sub_cpmk_id;
        $dipilih = $kontribusiLama !== null ? array_key_exists($id, $kontribusiLama) : array_key_exists($id, $kontribusiTersimpan);
        $nilai = $kontribusiLama !== null ? ($kontribusiLama[$id] ?? '') : Angka::ringkas($kontribusiTersimpan[$id] ?? null);

        return [$id => ['dipilih' => $dipilih, 'kontribusi' => (string) $nilai]];
    });
    $bobotAwal = old('bobot_asesmen', Angka::ringkas($rencanaAsesmen->bobot_asesmen));
@endphp

<x-layouts.app :judul="$rencanaAsesmen->exists ? 'Ubah Asesmen' : 'Tambah Asesmen'">
    <x-kartu class="max-w-4xl">
        <p class="mb-5 text-sm text-text-muted">
            Kelas: <span class="font-medium text-text">{{ $kelas->nama() }}</span> · {{ $kelas->periodeAkademik->nama() }}
        </p>

        <form
            method="POST"
            action="{{ $rencanaAsesmen->exists ? route('dosen.kelas.rencana-asesmen.update', [$kelas, $rencanaAsesmen]) : route('dosen.kelas.rencana-asesmen.store', $kelas) }}"
            x-data="formAsesmen(@js(['bobotAsesmen' => (string) $bobotAwal, 'subCpmk' => $statusSubCpmk]))"
            novalidate
        >
            @csrf
            @if ($rencanaAsesmen->exists)
                @method('PUT')
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <x-select label="Metode Penilaian" name="metode_penilaian_id" :pilihan="$daftarMetode" :value="$rencanaAsesmen->metode_penilaian_id" kosong="Pilih metode" />
                <x-input label="Nama Asesmen" name="nama_asesmen" :value="$rencanaAsesmen->nama_asesmen" keterangan="Mis. Kuis 1, Tugas 1, UTS." />
                <x-input
                    label="Bobot Asesmen (%)"
                    name="bobot_asesmen"
                    inputmode="decimal"
                    :value="$bobotAwal"
                    x-model="bobotAsesmen"
                    keterangan="Bobot terhadap nilai akhir MK. Total semua asesmen kelas seharusnya 100."
                />
                <x-input
                    label="Nilai Maksimum"
                    name="nilai_maksimum"
                    inputmode="decimal"
                    :value="Angka::ringkas($rencanaAsesmen->nilai_maksimum ?? 100)"
                    keterangan="Skala nilai asesmen ini, mis. 100. Nilai mahasiswa dinormalisasi ke 0–100."
                />
                <x-input label="Tanggal Asesmen" name="tanggal_asesmen" type="date" :value="$rencanaAsesmen->tanggal_asesmen?->format('Y-m-d')" keterangan="Boleh kosong." />
            </div>

            <fieldset class="mt-8">
                <legend class="text-base font-semibold">Sub-CPMK yang Diukur</legend>
                <p class="mt-1 mb-4 text-sm text-text-muted">
                    Centang Sub-CPMK yang diukur asesmen ini, lalu isi bobot kontribusinya dalam % nilai akhir MK
                    (satu sel matriks RPS). Jumlahnya harus sama dengan bobot asesmen.
                </p>

                @error('kontribusi')
                    <p class="mb-4 rounded-lg bg-danger-bg px-4 py-3 text-sm text-danger">{{ $message }}</p>
                @enderror

                <div class="divide-y divide-border rounded-lg border border-border">
                    @foreach ($daftarSubCpmk as $subCpmk)
                        @php($id = $subCpmk->sub_cpmk_id)
                        <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center">
                            <label class="flex min-h-11 flex-1 cursor-pointer items-start gap-3">
                                <input type="checkbox" x-model="subCpmk['{{ $id }}'].dipilih" class="mt-0.5 size-5 shrink-0 accent-primary-700" aria-label="Pilih {{ $subCpmk->kode_sub_cpmk }}">
                                <span class="min-w-0 text-sm">
                                    <span class="font-semibold">{{ $subCpmk->kode_sub_cpmk }}</span>
                                    <span class="text-text-muted">· {{ $subCpmk->pemetaanCpmkMk->cpmk->kode_cpmk }} · target {{ Angka::ringkas($subCpmk->bobot_sub_cpmk) }}%</span>
                                    <span class="block text-text-muted">{{ $subCpmk->deskripsi_sub_cpmk }}</span>
                                </span>
                            </label>
                            <div class="sm:w-40">
                                {{-- Isian hanya aktif (dan ikut terkirim) jika Sub-CPMK dicentang. --}}
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    name="kontribusi[{{ $id }}]"
                                    x-model="subCpmk['{{ $id }}'].kontribusi"
                                    x-bind:disabled="! subCpmk['{{ $id }}'].dipilih"
                                    @disabled(! $statusSubCpmk[$id]['dipilih'])
                                    value="{{ $statusSubCpmk[$id]['kontribusi'] }}"
                                    placeholder="Bobot (%)"
                                    aria-label="Bobot kontribusi {{ $subCpmk->kode_sub_cpmk }}"
                                    @class([
                                        'block min-h-11 w-full rounded-lg border px-3 text-base disabled:bg-neutral-bg disabled:text-text-muted sm:text-sm',
                                        'border-danger' => $errors->has('kontribusi.'.$id),
                                        'border-border' => ! $errors->has('kontribusi.'.$id),
                                    ])
                                >
                                @error('kontribusi.'.$id)
                                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Jumlah dihitung langsung di browser; pemeriksaan sebenarnya tetap di server. --}}
                <p class="mt-3 text-sm" x-cloak>
                    Jumlah kontribusi:
                    <span class="font-semibold" x-bind:class="sesuai ? 'text-success' : 'text-danger'" x-text="format(totalKontribusi)"></span>
                    dari bobot asesmen <span class="font-semibold" x-text="format(angka(bobotAsesmen))"></span>
                    <span x-show="sesuai && totalKontribusi > 0" class="text-success">(sudah sama)</span>
                </p>
            </fieldset>

            <x-form-aksi :batal="route('dosen.kelas.rencana-asesmen.index', $kelas)" />
        </form>
    </x-kartu>
</x-layouts.app>
