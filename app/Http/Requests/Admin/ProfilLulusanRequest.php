<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class ProfilLulusanRequest extends DataKurikulumRequest
{
    protected function namaParameter(): string
    {
        return 'profilLulusan';
    }

    public function rules(): array
    {
        $kurikulumId = $this->kurikulumId();

        return [
            ...$this->aturanKurikulum(),
            'kode_pl' => [
                'required', 'string', 'max:20',
                Rule::unique('profil_lulusan', 'kode_pl')->where('kurikulum_id', $kurikulumId)->ignore($this->dataLama()),
            ],
            'deskripsi_pl' => ['required', 'string'],
            'unsur' => ['nullable', 'string', 'max:255'],
            'sumber' => ['nullable', 'string', 'max:255'],
            // Profesi hanya boleh dari kurikulum yang sama (bagian 6 spesifikasi).
            'profesi' => ['nullable', 'array'],
            'profesi.*' => ['bail', 'uuid', Rule::exists('profesi', 'profesi_id')->where('kurikulum_id', $kurikulumId)],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_pl.unique' => 'Kode PL sudah dipakai di kurikulum ini.',
        ];
    }
}
