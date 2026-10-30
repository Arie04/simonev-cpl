<?php

namespace App\Policies;

use App\Models\MataKuliah;
use App\Models\User;

class MataKuliahPolicy
{
    /**
     * Dosen hanya melihat CPL, CPMK, dan Sub-CPMK mata kuliah yang pernah atau sedang ia ampu
     * (KF-07).
     */
    public function lihatSebagaiDosen(User $user, MataKuliah $mataKuliah): bool
    {
        return $user->dosen !== null
            && $mataKuliah->kelasKuliah()->whereHas('dosen', fn ($query) => $query->whereKey($user->dosen->dosen_id))->exists();
    }
}
