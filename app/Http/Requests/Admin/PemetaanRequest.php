<?php

namespace App\Http\Requests\Admin;

use App\Models\Kurikulum;
use App\Services\MatriksPemetaan;
use App\Support\Angka;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Menyimpan satu matriks pemetaan kurikulum (bagian 7.6.5 spesifikasi).
 */
class PemetaanRequest extends FormRequest
{
    private ?MatriksPemetaan $matriks = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Bobot boleh ditulis dengan koma desimal, mis. "12,5".
     */
    protected function prepareForValidation(): void
    {
        $bobot = $this->input('bobot');

        if (is_array($bobot)) {
            $this->merge(['bobot' => array_map(Angka::normalisasi(...), $bobot)]);
        }
    }

    public function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in(array_keys(MatriksPemetaan::JENIS))],
            'kurikulum_id' => ['bail', 'required', 'uuid', Rule::exists('kurikulum', 'kurikulum_id')],
            'terhubung' => ['nullable', 'array'],
            'terhubung.*' => ['string', 'distinct'],
            'bobot' => ['nullable', 'array'],
        ];
    }

    /**
     * Pemeriksaan yang butuh data matriks: pasangan harus dari kurikulum yang sama, bobot
     * pasangan terpilih wajib 0–100, dan pasangan CPMK–MK yang sudah punya Sub-CPMK tidak
     * boleh dilepas.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $matriks = $this->matriks();
                $kunciDipilih = $this->input('terhubung', []);

                foreach ($kunciDipilih as $kunci) {
                    if (! $matriks->kunciValid($kunci)) {
                        $validator->errors()->add('terhubung', 'Pemetaan hanya boleh menghubungkan data dari kurikulum yang sama.');

                        return;
                    }

                    $bobot = $this->input('bobot', [])[$kunci] ?? null;

                    if ($matriks->kolomBobot() !== null && (! is_numeric($bobot) || $bobot < 0 || $bobot > 100)) {
                        $validator->errors()->add("bobot.{$kunci}", 'Bobot harus berupa angka 0 sampai 100.');
                    }
                }

                $pasanganTerkunci = $matriks->pasanganTerkunci($kunciDipilih);

                if ($pasanganTerkunci !== []) {
                    $validator->errors()->add(
                        'terhubung',
                        'Pemetaan '.implode(', ', $pasanganTerkunci).' tidak bisa dilepas karena sudah memiliki Sub-CPMK.',
                    );
                }
            },
        ];
    }

    public function matriks(): MatriksPemetaan
    {
        return $this->matriks ??= new MatriksPemetaan(
            $this->input('jenis'),
            Kurikulum::findOrFail($this->input('kurikulum_id')),
        );
    }
}
