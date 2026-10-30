<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DosenPengampuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dosen_id' => [
                'bail', 'required', 'uuid', Rule::exists('dosen', 'dosen_id'),
                // UQ(kelas_kuliah_id, dosen_id)
                Rule::unique('dosen_kelas', 'dosen_id')->where('kelas_kuliah_id', $this->route('kelasKuliah')->kelas_kuliah_id),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['dosen_id' => 'dosen'];
    }

    public function messages(): array
    {
        return ['dosen_id.unique' => 'Dosen ini sudah mengampu kelas ini.'];
    }
}
