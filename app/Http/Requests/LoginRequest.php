<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    // Maksimal percobaan gagal per menit untuk satu identitas dari satu IP (bagian 6 spesifikasi).
    private const MAKS_PERCOBAAN = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identitas' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Mencocokkan identitas (email, NPM, atau NIDN) dan password, lalu login.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->pastikanTidakDibatasi();

        $user = User::cariBerdasarkanIdentitas($this->string('identitas')->trim()->value());

        if ($user === null || ! Auth::attempt(['user_id' => $user->user_id, 'password' => $this->input('password')])) {
            RateLimiter::hit($this->kunciPembatas());

            throw ValidationException::withMessages(['identitas' => __('auth.failed')]);
        }

        // Akun tanpa peran tidak bisa membuka fitur apa pun, jadi login ditolak (KD-26).
        if ($user->roles()->doesntExist()) {
            Auth::logout();

            throw ValidationException::withMessages(['identitas' => __('auth.tanpa_peran')]);
        }

        RateLimiter::clear($this->kunciPembatas());
    }

    /**
     * @throws ValidationException
     */
    private function pastikanTidakDibatasi(): void
    {
        if (! RateLimiter::tooManyAttempts($this->kunciPembatas(), self::MAKS_PERCOBAAN)) {
            return;
        }

        throw ValidationException::withMessages([
            'identitas' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->kunciPembatas())]),
        ]);
    }

    /**
     * Kunci pembatas = identitas (huruf kecil) + alamat IP.
     */
    private function kunciPembatas(): string
    {
        return Str::transliterate(Str::lower($this->string('identitas')->trim()).'|'.$this->ip());
    }
}
