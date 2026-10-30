<?php

namespace App\Http\Requests\Admin;

use App\Models\Mahasiswa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Menambah satu mahasiswa ke kelas.
 */
class PesertaKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mahasiswa_id' => [
                'bail', 'required', 'uuid', Rule::exists('mahasiswa', 'mahasiswa_id'),
                // UQ(kelas_kuliah_id, mahasiswa_id)
                Rule::unique('peserta_kelas', 'mahasiswa_id')->where('kelas_kuliah_id', $this->route('kelasKuliah')->kelas_kuliah_id),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['mahasiswa_id' => 'mahasiswa'];
    }

    public function messages(): array
    {
        return ['mahasiswa_id.unique' => 'Mahasiswa ini sudah terdaftar di kelas ini.'];
    }

    /**
     * Satu kelas per MK per periode untuk setiap mahasiswa (KD-37).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $kelasLain = $this->route('kelasKuliah')->kelasLainYangDiikuti(Mahasiswa::find($this->input('mahasiswa_id')));

                if ($kelasLain !== null) {
                    $validator->errors()->add('mahasiswa_id', "Mahasiswa ini sudah terdaftar di kelas {$kelasLain->nama_kelas} untuk mata kuliah yang sama pada periode ini.");
                }
            },
        ];
    }
}
