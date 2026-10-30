<?php

namespace App\Policies;

use App\Models\KelasKuliah;
use App\Models\User;

/**
 * Dosen hanya bisa membuka dan mengubah kelas yang ia ampu (bagian 3 dan 6 spesifikasi).
 */
class KelasKuliahPolicy
{
    /**
     * Mengelola rencana asesmen dan nilai kelas: hanya dosen yang terdaftar di dosen_kelas.
     */
    public function kelola(User $user, KelasKuliah $kelasKuliah): bool
    {
        return $user->dosen !== null
            && $kelasKuliah->dosen()->whereKey($user->dosen->dosen_id)->exists();
    }
}
