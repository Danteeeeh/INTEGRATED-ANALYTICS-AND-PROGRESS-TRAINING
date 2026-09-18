<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    public const AUDIENCE_INSTITUTION = 'institution';

    public const AUDIENCE_COURSE = 'course';

    public const AUDIENCE_CLASS = 'class';

    public const AUDIENCE_ROLE = 'role';

    public const AUDIENCE_USERS = 'users';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'title',
        'body',
        'audience_type',
        'course_id',
        'class_id',
        'target_role_id',
        'attachment_media_id',
        'is_pinned',
        'publish_at',
        'unpin_at',
        'created_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'bool',
            'publish_at' => 'datetime',
            'unpin_at' => 'datetime',
            'status' => 'string',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function targetRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'target_role_id');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'attachment_media_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function views(): HasMany
    {
        return $this->hasMany(AnnouncementView::class);
    }

    public function scopePublished($query)
    {
        return $query->where('announcements.status', self::STATUS_PUBLISHED);
    }

    public function scopeScheduled($query)
    {
        return $query->where('announcements.status', self::STATUS_SCHEDULED);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $user = User::find($userId);
            $q->where('audience_type', self::AUDIENCE_INSTITUTION)
                ->orWhere(function ($q2) use ($user) {
                    $q2->where('audience_type', self::AUDIENCE_ROLE)
                        ->where('target_role_id', $user->role_id ?? null);
                })
                ->orWhere(function ($q2) use ($userId) {
                    $q2->where('audience_type', self::AUDIENCE_COURSE)
                        ->whereIn('course_id', function ($sub) use ($userId) {
                            $sub->select('courses.id')
                                ->from('courses')
                                ->join('classes', 'classes.course_id', '=', 'courses.id')
                                ->join('enrollments', 'enrollments.class_id', '=', 'classes.id')
                                ->where('enrollments.student_id', $userId);
                        });
                })
                ->orWhere(function ($q2) use ($userId) {
                    $q2->where('audience_type', self::AUDIENCE_CLASS)
                        ->whereIn('class_id', function ($sub) use ($userId) {
                            $sub->select('class_id')
                                ->from('enrollments')
                                ->where('student_id', $userId);
                        });
                });
        });
    }
}
