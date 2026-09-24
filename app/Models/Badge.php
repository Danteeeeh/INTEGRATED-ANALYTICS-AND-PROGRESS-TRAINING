<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Badge extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_COURSE = 'course';

    public const TYPE_COMPETENCY = 'competency';

    public const TYPE_ACHIEVEMENT = 'achievement';

    public const TYPE_PARTICIPATION = 'participation';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'description',
        'icon',
        'badge_type',
        'course_id',
        'class_id',
        'criteria',
        'criteria_description',
        'issued_count',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'issued_count' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function awards(): HasMany
    {
        return $this->hasMany(BadgeAward::class);
    }
}
