<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Reset password oleh Admin. Tidak ada reset lewat email; halaman login meminta
 * pengguna menghubungi Admin (bagian 6 spesifikasi).
 */
class AkunPasswordController extends Controller
{
    public function update(ResetPasswordRequest $request, User $user): RedirectResponse
    {
        // Cast 'hashed' di model User meng-hash password sebelum disimpan.
        $user->update(['password' => $request->validated('password_baru')]);

        return redirect()->route('admin.akun.edit', $user)->with('sukses', "Password {$user->nama} berhasil direset.");
    }
}
