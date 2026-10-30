<dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-[14rem_1fr]">
    <dt class="text-text-muted">Kurikulum</dt>
    <dd class="font-medium">{{ $kurikulum->nama_kurikulum }} (berlaku {{ $kurikulum->tahun_berlaku }})</dd>
    <dt class="text-text-muted">Program Studi</dt>
    <dd>{{ $programStudi->nama_program_studi }}, {{ $programStudi->nama_fakultas }}, {{ $programStudi->nama_perguruan_tinggi }}</dd>
    <dt class="text-text-muted">Jenjang / Gelar</dt>
    <dd>{{ $programStudi->jenjang_pendidikan }} · {{ $programStudi->gelar_lulusan }}</dd>
    <dt class="text-text-muted">Akreditasi</dt>
    <dd>{{ $programStudi->peringkat_akreditasi ?? '–' }}</dd>
    <dt class="text-text-muted">Visi Keilmuan</dt>
    <dd>{{ $programStudi->visi_keilmuan ?? '–' }}</dd>
    <dt class="text-text-muted">Misi Program Studi</dt>
    <dd class="whitespace-pre-line">{{ $programStudi->misi_program_studi ?? '–' }}</dd>
</dl>

<div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
    @foreach ($jumlah as $label => $nilai)
        <div class="rounded-lg border border-border px-4 py-3">
            <p class="text-sm text-text-muted">{{ $label }}</p>
            <p class="text-2xl font-bold">{{ $nilai }}</p>
        </div>
    @endforeach
</div>
