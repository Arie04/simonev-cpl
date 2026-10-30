<?php

namespace App\Http\Requests\Admin;

class EvaluasiKurikulumRequest extends DataKurikulumRequest
{
    protected function namaParameter(): string
    {
        return 'evaluasiKurikulum';
    }

    public function rules(): array
    {
        return [
            ...$this->aturanKurikulum(),
            'tahap_evaluasi' => ['required', 'string', 'max:255'],
            'kinerja_mutu' => ['required', 'string'],
            'standar_kinerja_mutu' => ['required', 'string'],
            'kesenjangan' => ['nullable', 'string'],
            'tindak_lanjut' => ['nullable', 'string'],
        ];
    }
}
