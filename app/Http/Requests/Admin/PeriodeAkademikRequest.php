<?php

namespace App\Http\Requests\Admin;

use App\Models\PeriodeAkademik;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeriodeAkademikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Format "2026/2027": dua tahun berurutan.
            'tahun_akademik' => [
                'bail', 'required', 'string', 'regex:/^\d{4}\/\d{4}$/',
                function (string $attribute, mixed $value, Closure $fail) {
                    [$tahunAwal, $tahunAkhir] = array_map('intval', explode('/', $value));

                    if ($tahunAkhir !== $tahunAwal + 1) {
                        $fail('Tahun akademik harus dua tahun berurutan, mis. 2026/2027.');
                    }
                },
            ],
            'semester_periode' => ['required', Rule::in([PeriodeAkademik::GANJIL, PeriodeAkademik::GENAP])],
        ];
    }

    public function attributes(): array
    {
        return [
            'tahun_akademik' => 'tahun akademik',
            'semester_periode' => 'semester',
        ];
    }

    public function messages(): array
    {
        return [
            'tahun_akademik.regex' => 'Tahun akademik ditulis seperti 2026/2027.',
        ];
    }

    /**
     * ERD tidak memberi UQ untuk periode, jadi duplikasi dicek di aplikasi (bagian 6 spesifikasi).
     */
    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $sudahAda = PeriodeAkademik::where('tahun_akademik', $this->input('tahun_akademik'))
                    ->where('semester_periode', $this->input('semester_periode'))
                    ->when($this->route('periodeAkademik'), fn ($query, $periode) => $query->whereKeyNot($periode->getKey()))
                    ->exists();

                if ($sudahAda) {
                    $validator->errors()->add('tahun_akademik', "Periode {$this->input('tahun_akademik')} {$this->input('semester_periode')} sudah ada.");
                }
            },
        ];
    }
}
