<?php

namespace App\Http\Requests\Dosen;

use App\Support\Angka;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Simpan nilai satu asesmen untuk seluruh peserta sekaligus (bagian 7.6.8 spesifikasi).
 * Jika satu nilai tidak valid, tidak ada nilai yang disimpan.
 */
class NilaiRequest extends FormRequest
{
    /**
     * Hanya dosen pengampu kelas asesmen ini (KelasKuliahPolicy::kelola).
     */
    public function authorize(): bool
    {
        return $this->user()->can('kelola', $this->route('rencanaAsesmen')->kelasKuliah);
    }

    /**
     * Nilai boleh ditulis dengan koma maupun titik desimal (bagian 7.3).
     */
    protected function prepareForValidation(): void
    {
        $nilai = $this->input('nilai');

        if (is_array($nilai)) {
            $this->merge(['nilai' => array_map(Angka::normalisasi(...), $nilai)]);
        }
    }

    public function rules(): array
    {
        // 0 ≤ nilai ≤ nilai_maksimum asesmen (bagian 6).
        return [
            'nilai' => ['required', 'array'],
            'nilai.*' => ['nullable', 'numeric', 'min:0', 'max:'.$this->route('rencanaAsesmen')->nilai_maksimum],
        ];
    }

    public function messages(): array
    {
        return [
            'nilai.*.numeric' => 'Nilai harus berupa angka.',
            'nilai.*.min' => 'Nilai tidak boleh kurang dari 0.',
            'nilai.*.max' => 'Nilai tidak boleh lebih dari nilai maksimum ('.Angka::ringkas($this->route('rencanaAsesmen')->nilai_maksimum).').',
        ];
    }

    /**
     * Mahasiswa yang bisa dinilai hanya peserta kelas asesmen ini (bagian 6).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! is_array($this->input('nilai'))) {
                    return;
                }

                $idPeserta = $this->route('rencanaAsesmen')->kelasKuliah->pesertaKelas()->pluck('peserta_kelas_id')->all();

                if (array_diff(array_keys($this->input('nilai')), $idPeserta) !== []) {
                    $validator->errors()->add('nilai', 'Mahasiswa yang bisa dinilai hanya peserta kelas ini.');
                }
            },
        ];
    }
}
