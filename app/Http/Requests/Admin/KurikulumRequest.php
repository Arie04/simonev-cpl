<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KurikulumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_studi_id' => ['bail', 'required', 'uuid', Rule::exists('program_studi', 'program_studi_id')],
            'nama_kurikulum' => ['required', 'string', 'max:255'],
            // Tahun berlaku unik, karena kurikulum terbaru ditentukan dari tahun ini.
            'tahun_berlaku' => [
                'required', 'integer', 'between:2000,2100',
                Rule::unique('kurikulum', 'tahun_berlaku')->ignore($this->route('kurikulum')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tahun_berlaku.unique' => 'Sudah ada kurikulum dengan tahun berlaku ini.',
        ];
    }
}
