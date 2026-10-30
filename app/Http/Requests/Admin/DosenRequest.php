<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DosenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dosen = $this->route('dosen');

        return [
            'nama' => ['required', 'string', 'max:255'],
            'nidn' => ['required', 'alpha_num', 'max:20', Rule::unique('dosen', 'nidn')->ignore($dosen)],
            // Email boleh kosong karena dosen bisa login dengan NIDN (KD-19).
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($dosen?->user_id, 'user_id')],
            // Password awal diisi Admin saat menambah; untuk mengganti, pakai reset password di Akun Pengguna.
            'password' => $dosen === null ? ['required', 'string', 'min:8'] : ['exclude'],
        ];
    }

    public function attributes(): array
    {
        return [
            'password' => 'password awal',
        ];
    }
}
