<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Tambah/ubah akun di menu Akun Pengguna (KD-36).
 */
class AkunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $punyaDataDosenAtauMahasiswa = $user !== null && ($user->dosen !== null || $user->mahasiswa !== null);

        return [
            'nama' => ['required', 'string', 'max:255'],
            // Akun tanpa data dosen/mahasiswa (mis. Admin) login dengan email, jadi email wajib (KD-19).
            'email' => [
                Rule::requiredIf(! $punyaDataDosenAtauMahasiswa), 'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'password' => $user === null ? ['required', 'string', 'min:8'] : ['exclude'],
            'peran' => ['required', 'array', 'min:1'],
            'peran.*' => ['string', 'distinct', Rule::in(($user ?? new User)->peranYangBoleh())],
        ];
    }

    public function attributes(): array
    {
        return [
            'password' => 'password awal',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi untuk akun yang tidak punya data dosen atau mahasiswa.',
            'peran.required' => 'Pilih minimal satu peran.',
            'peran.*.in' => 'Peran Dosen atau Mahasiswa hanya untuk akun yang punya data dosen atau mahasiswa. Tambahkan lewat menu Data Master Dosen atau Mahasiswa.',
        ];
    }

    /**
     * Admin tidak bisa mencabut peran Admin dari akunnya sendiri, supaya sistem tidak
     * kehilangan Admin (KD-36).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->route('user');
                $peranDipilih = (array) $this->input('peran', []);

                if ($user !== null && $user->is($this->user()) && ! in_array(Role::ADMIN, $peranDipilih, true)) {
                    $validator->errors()->add('peran', 'Anda tidak bisa mencabut peran Admin dari akun Anda sendiri.');
                }
            },
        ];
    }
}
