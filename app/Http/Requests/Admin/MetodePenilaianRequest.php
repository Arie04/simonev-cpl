<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MetodePenilaianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode_metode' => [
                'required', 'string', 'max:20',
                Rule::unique('metode_penilaian', 'kode_metode')->ignore($this->route('metodePenilaian')),
            ],
            'nama_metode' => ['required', 'string', 'max:100'],
        ];
    }
}
