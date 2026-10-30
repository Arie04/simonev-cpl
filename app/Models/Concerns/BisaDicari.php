<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Pencarian "Cari data..." di halaman daftar. Model yang memakai trait ini
 * menuliskan kolom yang dicari di properti $kolomPencarian.
 */
trait BisaDicari
{
    public function scopeCari(Builder $query, ?string $kataKunci): void
    {
        if (blank($kataKunci)) {
            return;
        }

        // Tanda % dan _ dari pengguna diperlakukan sebagai huruf biasa, bukan wildcard LIKE.
        $pola = '%'.addcslashes(trim($kataKunci), '\\%_').'%';

        $query->where(function (Builder $query) use ($pola) {
            foreach ($this->kolomPencarian as $kolom) {
                $query->orWhere($kolom, 'like', $pola);
            }
        });
    }
}
