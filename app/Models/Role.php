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

    /**
     * AA Staff / Academic Officer — view-only in the LMS. The official data
     * and final grades stay in the Academic Affairs (AA) system; this role
     * exists so AA can look (not edit) at what the LMS holds.
     */
    public const AA_STAFF = 'aa_staff';

    protected $fillable = ['name', 'slug', 'description'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}
