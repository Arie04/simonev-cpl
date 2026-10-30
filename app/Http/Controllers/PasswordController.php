<?php

namespace App\Http\Controllers;

use App\Http\Requests\UbahPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('password.edit');
    }

    public function update(UbahPasswordRequest $request): RedirectResponse
    {
        // Cast 'hashed' di model User meng-hash password sebelum disimpan.
        $request->user()->update(['password' => $request->validated('password')]);

        return redirect()->route('password.edit')->with('sukses', 'Password berhasil diubah.');
    }
}
