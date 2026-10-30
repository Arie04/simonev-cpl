<?php

namespace App\Http\Requests\Admin;

use App\Models\Cpmk;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class CpmkRequest extends DataKurikulumRequest
{
    protected function namaParameter(): string
    {
        return 'cpmk';
    }

    /**
     * Tabel cpmk tidak punya kolom kurikulum_id; kurikulumnya diambil lewat CPL.
     */
    protected function kurikulumId(): mixed
    {
        return $this->dataLama()?->cpl->kurikulum_id ?? $this->input('kurikulum_id');
    }

    public function rules(): array
    {
        $kurikulumId = $this->kurikulumId();
        $cpmk = $this->dataLama();

        return [
            ...$this->aturanKurikulum(),
            // CPL harus dari kurikulum yang sama.
            'cpl_id' => ['bail', 'required', 'uuid', Rule::exists('cpl', 'cpl_id')->where('kurikulum_id', $kurikulumId)],
            // UQ(cpl_id, kode_cpmk) di database, tetapi di aplikasi kode CPMK unik dalam satu
            // kurikulum (bagian 4 spesifikasi), jadi dicek lewat CPL-nya.
            'kode_cpmk' => [
                'required', 'string', 'max:20',
                function (string $attribute, mixed $value, Closure $fail) use ($kurikulumId, $cpmk) {
                    $sudahDipakai = Cpmk::where('kode_cpmk', $value)
                        ->whereHas('cpl', fn (Builder $query) => $query->where('kurikulum_id', $kurikulumId))
                        ->when($cpmk, fn (Builder $query) => $query->whereKeyNot($cpmk->getKey()))
                        ->exists();

                    if ($sudahDipakai) {
                        $fail('Kode CPMK sudah dipakai di kurikulum ini.');
                    }
                },
            ],
            'deskripsi_cpmk' => ['required', 'string'],
        ];
    }

    /**
     * CPL dari CPMK yang sudah dipetakan ke mata kuliah tidak bisa diganti, supaya capaian
     * CPL kelas yang sudah berjalan tidak berubah diam-diam (KD-50).
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $cpmk = $this->dataLama();

                if ($cpmk !== null
                    && $this->input('cpl_id') !== $cpmk->cpl_id
                    && $cpmk->pemetaanCpmkMk()->exists()) {
                    $validator->errors()->add('cpl_id', 'CPL tidak bisa diganti karena CPMK ini sudah dipetakan ke mata kuliah.');
                }
            },
        ];
    }
}
