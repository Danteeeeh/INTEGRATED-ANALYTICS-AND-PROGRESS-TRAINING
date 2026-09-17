<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradeCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_id',
        'name',
        'description',
        'weight_percent',
        'drop_lowest',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'weight_percent' => 'decimal:2',
            'drop_lowest' => 'integer',
            'position' => 'integer',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GradeItem::class)->orderBy('position');
    }
}
