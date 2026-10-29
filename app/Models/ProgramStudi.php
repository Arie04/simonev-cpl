<?php

namespace App\Models;

use Database\Factories\ProgramStudiFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramStudi extends Model
{
    /** @use HasFactory<ProgramStudiFactory> */
    use HasFactory, HasUuids;

    protected $table = 'program_studi';

    protected $primaryKey = 'program_studi_id';

    protected $fillable = [
        'nama_perguruan_tinggi',
        'nama_fakultas',
        'nama_program_studi',
        'izin_pendirian',
        'peringkat_akreditasi',
        'jenjang_pendidikan',
        'gelar_lulusan',
        'visi_keilmuan',
        'misi_program_studi',
    ];

    public function kurikulum(): HasMany
    {
        return $this->hasMany(Kurikulum::class, 'program_studi_id', 'program_studi_id');
    }
}
