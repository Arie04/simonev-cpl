<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class BahanKajianRequest extends DataKurikulumRequest
{
    protected function namaParameter(): string
    {
        return 'bahanKajian';
    }

    public function rules(): array
    {
        return [
            ...$this->aturanKurikulum(),
            'kode_bk' => [
                'required', 'string', 'max:20',
                Rule::unique('bahan_kajian', 'kode_bk')->where('kurikulum_id', $this->kurikulumId())->ignore($this->dataLama()),
            ],
            'nama_bahan_kajian' => ['required', 'string', 'max:255'],
            'kategori_bk' => ['nullable', 'string', 'max:50'],
            // Bobot tingkat kepentingan CC2020: bilangan bulat, tanpa batas maksimal 5 (KD-22).
            'bobot_min' => ['nullable', 'integer', 'min:0', 'max:255'],
            'bobot_max' => ['nullable', 'integer', 'min:0', 'max:255', Rule::when($this->filled('bobot_min'), 'gte:bobot_min')],
            // Jam CS2023: kosong berarti BK tidak bersumber dari CS2023, 0 berarti 0 jam.
            'cs_core' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'ka_core' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'referensi' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_bk.unique' => 'Kode BK sudah dipakai di kurikulum ini.',
            'bobot_max.gte' => 'Bobot maksimum tidak boleh lebih kecil dari bobot minimum.',
        ];
    }
}
