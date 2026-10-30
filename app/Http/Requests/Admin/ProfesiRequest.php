<?php

namespace App\Http\Requests\Admin;

class ProfesiRequest extends DataKurikulumRequest
{
    protected function namaParameter(): string
    {
        return 'profesi';
    }

    public function rules(): array
    {
        return [
            ...$this->aturanKurikulum(),
            'nama_profesi' => ['required', 'string', 'max:255'],
        ];
    }
}
