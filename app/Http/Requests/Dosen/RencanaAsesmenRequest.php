<?php

namespace App\Http\Requests\Dosen;

use App\Support\Angka;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Rencana asesmen beserta pemetaannya ke Sub-CPMK (KF-08, KF-09, bagian 6 spesifikasi).
 */
class RencanaAsesmenRequest extends FormRequest
{
    /**
     * Hanya dosen pengampu kelas ini (KelasKuliahPolicy::kelola).
     */
    public function authorize(): bool
    {
        return $this->user()->can('kelola', $this->route('kelasKuliah'));
    }

    /**
     * Angka boleh ditulis dengan koma desimal, mis. "12,5".
     */
    protected function prepareForValidation(): void
    {
        $kontribusi = $this->input('kontribusi');

        $this->merge([
            'bobot_asesmen' => Angka::normalisasi($this->input('bobot_asesmen')),
            'nilai_maksimum' => Angka::normalisasi($this->input('nilai_maksimum')),
            'kontribusi' => is_array($kontribusi) ? array_map(Angka::normalisasi(...), $kontribusi) : $kontribusi,
        ]);
    }

    public function rules(): array
    {
        $nilaiTertinggi = $this->route('rencanaAsesmen')?->nilaiTertinggi();

        return [
            'metode_penilaian_id' => ['bail', 'required', 'uuid', Rule::exists('metode_penilaian', 'metode_penilaian_id')],
            'nama_asesmen' => ['required', 'string', 'max:255'],
            'bobot_asesmen' => ['required', 'numeric', 'between:0,100'],
            // nilai_maksimum > 0, dan tidak boleh di bawah nilai yang sudah diinput (KD-48).
            'nilai_maksimum' => ['required', 'numeric', 'gt:0', 'max:9999.99', ...($nilaiTertinggi !== null ? ['min:'.$nilaiTertinggi] : [])],
            'tanggal_asesmen' => ['nullable', 'date'],
            // Kunci kontribusi adalah id Sub-CPMK, nilainya bobot kontribusi (% nilai akhir MK).
            'kontribusi' => ['required', 'array', 'min:1'],
            'kontribusi.*' => ['required', 'numeric', 'between:0,100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'metode_penilaian_id' => 'metode penilaian',
            'nama_asesmen' => 'nama asesmen',
            'bobot_asesmen' => 'bobot asesmen',
            'nilai_maksimum' => 'nilai maksimum',
            'tanggal_asesmen' => 'tanggal asesmen',
            'kontribusi' => 'Sub-CPMK',
            'kontribusi.*' => 'bobot kontribusi',
        ];
    }

    public function messages(): array
    {
        return [
            'kontribusi.required' => 'Pilih minimal satu Sub-CPMK yang diukur asesmen ini.',
            'nilai_maksimum.min' => 'Nilai maksimum tidak boleh lebih kecil dari nilai tertinggi yang sudah diinput ('
                .Angka::ringkas($this->route('rencanaAsesmen')?->nilaiTertinggi()).').',
        ];
    }

    /**
     * Sub-CPMK harus milik MK kelas ini, dan Σ bobot kontribusi harus sama dengan bobot
     * asesmen. Keduanya diisi di form yang sama, jadi perbedaan diblokir (bagian 6).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $kontribusi = $this->input('kontribusi');
                $idSubCpmkBoleh = $this->route('kelasKuliah')->daftarSubCpmk()->modelKeys();

                if (array_diff(array_keys($kontribusi), $idSubCpmkBoleh) !== []) {
                    $validator->errors()->add('kontribusi', 'Sub-CPMK yang dipilih harus milik mata kuliah kelas ini.');

                    return;
                }

                $totalKontribusi = array_sum($kontribusi);

                if (! Angka::sama($totalKontribusi, $this->input('bobot_asesmen'))) {
                    $validator->errors()->add(
                        'kontribusi',
                        'Jumlah bobot kontribusi ('.Angka::ringkas($totalKontribusi).') harus sama dengan bobot asesmen ('.Angka::ringkas($this->input('bobot_asesmen')).').',
                    );
                }
            },
        ];
    }
}
