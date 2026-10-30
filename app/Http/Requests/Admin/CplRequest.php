<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class CplRequest extends DataKurikulumRequest
{
    protected function namaParameter(): string
    {
        return 'cpl';
    }

    public function rules(): array
    {
        return [
            ...$this->aturanKurikulum(),
            'kode_cpl' => [
                'required', 'string', 'max:20',
                Rule::unique('cpl', 'kode_cpl')->where('kurikulum_id', $this->kurikulumId())->ignore($this->dataLama()),
            ],
            'kategori_cpl' => ['required', 'string', 'max:50'],
            'deskripsi_cpl' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_cpl.unique' => 'Kode CPL sudah dipakai di kurikulum ini.',
        ];
    }
}
