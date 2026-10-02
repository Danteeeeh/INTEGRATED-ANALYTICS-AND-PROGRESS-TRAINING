<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['department_id', 'name', 'code', 'description'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            Section::class,
            'program_id', // FK on sections -> programs.id
            'section_id', // FK on users -> sections.id
            'id',         // local key on programs
            'id'          // local key on sections
        );
    }

    public function instructors(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            Section::class,
            'program_id',
            'section_id',
            'id',
            'id'
        )->whereHas('role', fn ($q) => $q->where('slug', Role::INSTRUCTOR));
    }
}
