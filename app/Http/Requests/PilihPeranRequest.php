<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PilihPeranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => [
                'bail',
                'required',
                'uuid',
                // Hanya peran yang memang dimiliki akun ini.
                Rule::exists('user_roles', 'role_id')->where('user_id', $this->user()->user_id),
            ],
        ];
    }
}
