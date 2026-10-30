<?php

namespace App\Http\Requests\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dasar Form Request untuk data yang milik satu kurikulum (PL, profesi, CPL, BK, MK, CPMK, ...).
 *
 * Data baru masuk ke kurikulum yang sedang dipilih (isian tersembunyi kurikulum_id),
 * sedangkan kurikulum data lama tidak bisa dipindah. Kode-kode dicek unik per kurikulum (KD-07).
 */
abstract class DataKurikulumRequest extends FormRequest
{
    /**
     * Nama parameter route untuk data yang sedang diubah, mis. 'cpl'.
     */
    abstract protected function namaParameter(): string;

    public function authorize(): bool
    {
        // Hak akses sudah dibatasi middleware peran:ADMIN di routes/web.php.
        return true;
    }

    /**
     * Data lama saat diubah, atau null saat menambah data baru.
     */
    protected function dataLama(): ?Model
    {
        return $this->route($this->namaParameter());
    }

    protected function kurikulumId(): mixed
    {
        return $this->dataLama()?->kurikulum_id ?? $this->input('kurikulum_id');
    }

    /**
     * Aturan kurikulum_id, hanya saat menambah data.
     */
    protected function aturanKurikulum(): array
    {
        if ($this->dataLama() !== null) {
            return [];
        }

        return [
            'kurikulum_id' => ['bail', 'required', 'uuid', Rule::exists('kurikulum', 'kurikulum_id')],
        ];
    }
}
