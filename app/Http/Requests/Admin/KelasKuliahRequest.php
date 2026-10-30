<?php

namespace App\Http\Requests\Admin;

use App\Models\KelasKuliah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KelasKuliahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periode_akademik_id' => ['bail', 'required', 'uuid', Rule::exists('periode_akademik', 'periode_akademik_id')],
            'mata_kuliah_id' => ['bail', 'required', 'uuid', Rule::exists('mata_kuliah', 'mata_kuliah_id')],
            'nama_kelas' => ['required', 'string', 'max:10'],
        ];
    }

    public function attributes(): array
    {
        return [
            'periode_akademik_id' => 'periode akademik',
            'mata_kuliah_id' => 'mata kuliah',
            'nama_kelas' => 'nama kelas',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $kelas = $this->route('kelasKuliah');

                // ERD tidak memberi UQ untuk kelas, jadi duplikasi MK + periode + nama kelas dicek di sini.
                $sudahAda = KelasKuliah::where('mata_kuliah_id', $this->input('mata_kuliah_id'))
                    ->where('periode_akademik_id', $this->input('periode_akademik_id'))
                    ->where('nama_kelas', $this->input('nama_kelas'))
                    ->when($kelas, fn ($query) => $query->whereKeyNot($kelas->getKey()))
                    ->exists();

                if ($sudahAda) {
                    $validator->errors()->add('nama_kelas', 'Kelas ini sudah ada untuk mata kuliah dan periode yang sama.');
                }

                $gantiMataKuliahAtauPeriode = $kelas !== null
                    && ($this->input('mata_kuliah_id') !== $kelas->mata_kuliah_id
                        || $this->input('periode_akademik_id') !== $kelas->periode_akademik_id);

                if ($gantiMataKuliahAtauPeriode && ! $kelas->bisaGantiMataKuliahDanPeriode()) {
                    $validator->errors()->add('mata_kuliah_id', 'Mata kuliah dan periode tidak bisa diganti karena kelas sudah punya peserta atau rencana asesmen.');
                }
            },
        ];
    }
}
