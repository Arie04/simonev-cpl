<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UbahPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'password' => 'password baru',
        ];
    }

    public function messages(): array
    {
        return [
            'password_lama.current_password' => 'Password lama salah.',
        ];
    }
}
