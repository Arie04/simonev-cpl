<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rincian penilaian Sub-CPMK: metode, teknik, instrumen, dan kriteria (bagian 7.6.4 spesifikasi).
 */
class SubCpmkPenilaianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // UQ(sub_cpmk_id, metode_penilaian_id): satu metode cukup sekali per Sub-CPMK.
            'metode_penilaian_id' => [
                'bail', 'required', 'uuid', Rule::exists('metode_penilaian', 'metode_penilaian_id'),
                Rule::unique('sub_cpmk_penilaian', 'metode_penilaian_id')
                    ->where('sub_cpmk_id', $this->route('subCpmk')->sub_cpmk_id)
                    ->ignore($this->route('penilaian')),
            ],
            'teknik_penilaian' => ['nullable', 'string', 'max:255'],
            'instrumen' => ['nullable', 'string', 'max:255'],
            'kriteria' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'metode_penilaian_id' => 'metode penilaian',
            'teknik_penilaian' => 'teknik penilaian',
            'instrumen' => 'instrumen',
            'kriteria' => 'kriteria',
        ];
    }

    public function messages(): array
    {
        return [
            'metode_penilaian_id.unique' => 'Metode ini sudah ada di rincian penilaian Sub-CPMK ini.',
        ];
    }
}
