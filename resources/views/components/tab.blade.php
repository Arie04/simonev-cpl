@props(['daftar', 'aktif', 'nama' => 'tab'])

{{-- Tab bergaya garis bawah; tab aktif bertulisan primary-700 dengan garis bawah 2px (bagian 7.3).
     Setiap tab adalah tautan biasa yang membawa filter lain di alamat halaman. Di layar sempit
     deretan tab bisa digeser di dalam wadahnya, bukan seluruh halaman. --}}
<nav
    {{ $attributes->class('-mx-1 mb-6 overflow-x-auto border-b border-border') }}
    aria-label="Tab"
    x-data
    x-init="$el.querySelector('[aria-current]')?.scrollIntoView({ block: 'nearest', inline: 'center' })"
>
    <ul class="flex min-w-max gap-1 px-1">
        @foreach ($daftar as $kunci => $label)
            <li>
                <a
                    href="{{ request()->fullUrlWithQuery([$nama => $kunci, 'page' => null]) }}"
                    @class([
                        '-mb-px inline-flex min-h-11 items-center border-b-2 px-4 text-sm font-medium whitespace-nowrap',
                        'border-primary-700 text-primary-700' => $kunci === $aktif,
                        'border-transparent text-text-muted hover:border-border hover:text-text' => $kunci !== $aktif,
                    ])
                    @if ($kunci === $aktif) aria-current="page" @endif
                >{{ $label }}</a>
            </li>
        @endforeach
    </ul>
</nav>
