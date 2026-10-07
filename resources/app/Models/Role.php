<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const ADMIN = 'admin';

    public const INSTRUCTOR = 'instructor';

    public const STUDENT = 'student';

    public const REGISTRAR = 'registrar';

    protected $fillable = ['name', 'slug', 'description'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    /**
     * Roles that may still be assigned to a user.
     *
     * The registrar role was retired from the system (its routes were removed
     * and it is excluded from analytics and user listings), so it must not
     * appear in any role picker or filter dropdown — otherwise an admin can
     * still hand the dead role out.
     */
    public function scopeAssignable($query)
    {
        return $query->where('slug', '!=', self::REGISTRAR);
    }
}
