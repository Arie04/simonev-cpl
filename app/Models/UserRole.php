<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot akun dan peran.
 *
 * Dibuat sebagai model sendiri (dipasang lewat ->using()) agar PK UUID tetap
 * terisi saat baris dibuat lewat relasi, mis. attach() atau sync().
 */
class UserRole extends Pivot
{
    use HasUuids;

    protected $table = 'user_roles';

    protected $primaryKey = 'user_role_id';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }
}
