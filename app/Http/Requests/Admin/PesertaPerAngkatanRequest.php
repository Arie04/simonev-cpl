<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Menambah semua mahasiswa satu angkatan ke kelas sekaligus (bagian 7.6.6 spesifikasi).
 */
class PesertaPerAngkatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'angkatan' => ['required', 'integer', Rule::exists('mahasiswa', 'angkatan')],
        ];
    }

    public function messages(): array
    {
        return ['angkatan.exists' => 'Belum ada mahasiswa di angkatan ini.'];
    }
}
