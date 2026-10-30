<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MahasiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mahasiswa = $this->route('mahasiswa');

        return [
            'nama' => ['required', 'string', 'max:255'],
            // Kolom database tetap `nim` sesuai ERD, tetapi tampilannya "NPM" (bagian 13 spesifikasi).
            'nim' => ['required', 'alpha_num', 'max:20', Rule::unique('mahasiswa', 'nim')->ignore($mahasiswa)],
            'angkatan' => ['required', 'integer', 'between:2000,2100'],
            // Email boleh kosong karena mahasiswa bisa login dengan NPM (KD-19).
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($mahasiswa?->user_id, 'user_id')],
            // Password awal diisi Admin saat menambah; untuk mengganti, pakai reset password di Akun Pengguna.
            'password' => $mahasiswa === null ? ['required', 'string', 'min:8'] : ['exclude'],
        ];
    }

    public function attributes(): array
    {
        return [
            'password' => 'password awal',
        ];
    }
}
