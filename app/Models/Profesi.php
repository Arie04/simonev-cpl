<?php

namespace App\Models;

use Database\Factories\ProfesiFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Profesi extends Model
{
    /** @use HasFactory<ProfesiFactory> */
    use HasFactory, HasUuids;

    protected $table = 'profesi';

    protected $primaryKey = 'profesi_id';

    protected $fillable = ['kurikulum_id', 'nama_profesi'];

    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class, 'kurikulum_id', 'kurikulum_id');
    }

    public function profilLulusan(): BelongsToMany
    {
        return $this->belongsToMany(ProfilLulusan::class, 'profil_lulusan_profesi', 'profesi_id', 'profil_lulusan_id')
            ->using(ProfilLulusanProfesi::class)
            ->withTimestamps();
    }
}
