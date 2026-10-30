<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class MataKuliahRequest extends DataKurikulumRequest
{
    protected function namaParameter(): string
    {
        return 'mataKuliah';
    }

    public function rules(): array
    {
        return [
            ...$this->aturanKurikulum(),
            'kode_mata_kuliah' => [
                'required', 'string', 'max:20',
                Rule::unique('mata_kuliah', 'kode_mata_kuliah')->where('kurikulum_id', $this->kurikulumId())->ignore($this->dataLama()),
            ],
            'nama_mata_kuliah' => ['required', 'string', 'max:255'],
            // Batas semester dan SKS mengikuti KD-42.
            'semester' => ['required', 'integer', 'between:1,8'],
            'jumlah_sks' => ['required', 'integer', 'between:1,24'],
            'jenis_mata_kuliah' => ['required', 'string', 'max:50'],
            'level_bloom_mk' => ['nullable', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_mata_kuliah.unique' => 'Kode mata kuliah sudah dipakai di kurikulum ini.',
        ];
    }
}
