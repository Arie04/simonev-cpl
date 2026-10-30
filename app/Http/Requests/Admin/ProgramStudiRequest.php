<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProgramStudiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_perguruan_tinggi' => ['required', 'string', 'max:255'],
            'nama_fakultas' => ['required', 'string', 'max:255'],
            'nama_program_studi' => ['required', 'string', 'max:255'],
            'izin_pendirian' => ['nullable', 'string', 'max:255'],
            'peringkat_akreditasi' => ['nullable', 'string', 'max:50'],
            'jenjang_pendidikan' => ['required', 'string', 'max:50'],
            'gelar_lulusan' => ['required', 'string', 'max:50'],
            'visi_keilmuan' => ['nullable', 'string'],
            'misi_program_studi' => ['nullable', 'string'],
        ];
    }
}
