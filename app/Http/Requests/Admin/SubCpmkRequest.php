<?php

namespace App\Http\Requests\Admin;

use App\Support\Angka;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubCpmkRequest extends DataKurikulumRequest
{
    protected function namaParameter(): string
    {
        return 'subCpmk';
    }

    /**
     * Tabel sub_cpmk tidak punya kolom kurikulum_id; kurikulumnya diambil lewat mata kuliah
     * pada pasangan CPMK–MK.
     */
    protected function kurikulumId(): mixed
    {
        return $this->dataLama()?->pemetaanCpmkMk->mataKuliah->kurikulum_id ?? $this->input('kurikulum_id');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['bobot_sub_cpmk' => Angka::normalisasi($this->input('bobot_sub_cpmk'))]);
    }

    public function rules(): array
    {
        $kurikulumId = $this->kurikulumId();

        return [
            ...$this->aturanKurikulum(),
            // Pasangan CPMK–MK harus milik kurikulum yang sama.
            'pemetaan_cpmk_mk_id' => [
                'bail', 'required', 'uuid',
                Rule::exists('pemetaan_cpmk_mk', 'pemetaan_cpmk_mk_id')->where(
                    fn (Builder $query) => $query->whereIn(
                        'mata_kuliah_id',
                        fn (Builder $subquery) => $subquery->select('mata_kuliah_id')->from('mata_kuliah')->where('kurikulum_id', $kurikulumId),
                    ),
                ),
            ],
            // Kode boleh sama di pasangan CPMK–MK lain, seperti Tabel 15 APTIKOM (bagian 4).
            'kode_sub_cpmk' => [
                'required', 'string', 'max:20',
                Rule::unique('sub_cpmk', 'kode_sub_cpmk')
                    ->where('pemetaan_cpmk_mk_id', $this->input('pemetaan_cpmk_mk_id'))
                    ->ignore($this->dataLama()),
            ],
            'deskripsi_sub_cpmk' => ['required', 'string'],
            'bobot_sub_cpmk' => ['required', 'numeric', 'between:0,100'],
            'materi' => ['nullable', 'string'],
            'pertemuan_mulai' => ['nullable', 'integer', 'between:1,16'],
            'pertemuan_selesai' => ['nullable', 'integer', 'between:1,16', Rule::when($this->filled('pertemuan_mulai'), 'gte:pertemuan_mulai')],
            'catatan' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pemetaan_cpmk_mk_id' => 'pasangan CPMK–MK',
            'kode_sub_cpmk' => 'kode Sub-CPMK',
            'deskripsi_sub_cpmk' => 'deskripsi Sub-CPMK',
            'bobot_sub_cpmk' => 'bobot Sub-CPMK',
            'materi' => 'materi',
            'pertemuan_mulai' => 'pertemuan mulai',
            'pertemuan_selesai' => 'pertemuan selesai',
            'catatan' => 'catatan',
        ];
    }

    public function messages(): array
    {
        return [
            'kode_sub_cpmk.unique' => 'Kode Sub-CPMK sudah dipakai di pasangan CPMK–MK ini.',
            'pertemuan_selesai.gte' => 'Pertemuan selesai tidak boleh sebelum pertemuan mulai.',
        ];
    }

    /**
     * Pasangan CPMK–MK dari Sub-CPMK yang sudah dipakai rencana asesmen tidak bisa diganti,
     * supaya capaian kelas yang sudah berjalan tidak berubah diam-diam (KD-54).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $subCpmk = $this->dataLama();

                if ($subCpmk !== null
                    && $this->input('pemetaan_cpmk_mk_id') !== $subCpmk->pemetaan_cpmk_mk_id
                    && $subCpmk->rencanaAsesmen()->exists()) {
                    $validator->errors()->add('pemetaan_cpmk_mk_id', 'Pasangan CPMK–MK tidak bisa diganti karena Sub-CPMK ini sudah dipakai rencana asesmen.');
                }
            },
        ];
    }
}
