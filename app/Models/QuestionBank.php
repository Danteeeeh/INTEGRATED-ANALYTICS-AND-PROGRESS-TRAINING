<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class QuestionBank extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT = 'draft';

    const STATUS_ACTIVE = 'active';

    const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'title',
        'code',
        'description',
        'category',
        'course_id',
        'class_id',
        'created_by',
        'is_shared',
        'status',
    ];

    protected $casts = [
        'is_shared' => 'bool',
        'status' => 'string',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function scopeActive($query)
    {
        return $query->where('question_banks.status', self::STATUS_ACTIVE);
    }

    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    /**
     * Banks this viewer may add a question to.
     *
     * Own banks, plus — for an admin — the ones an admin has shared. Somebody
     * else's private bank stays read-only, so a question can never be parked in
     * a bank its author cannot maintain.
     *
     * Single definition of the rule, on purpose: the Test Bank's bank picker
     * and the check that guards question creation used to state it separately,
     * and the picker silently came back empty when the two drifted.
     */
    public function scopeWritableBy($query, ?User $user)
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($user) {
            $q->where('created_by', $user->id);

            if ($user->isAdmin()) {
                $q->orWhere('is_shared', true);
            }
        });
    }

    /**
     * Whether this viewer may add a question to this bank.
     */
    public function isWritableBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->created_by === $user->id
            || ($user->isAdmin() && (bool) $this->is_shared);
    }
}
