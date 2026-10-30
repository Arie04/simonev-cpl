<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function show(Request $request): View
    {
        return view('profil.show', [
            'user' => $request->user()->load(['roles', 'dosen', 'mahasiswa']),
        ]);
    }
}
