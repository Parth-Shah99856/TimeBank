<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'idea_id',
        'lead_user_id',
        'category_id',
        'title',
        'description',
        'target_hours',
        'hours_contributed',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_hours' => 'decimal:2',
            'hours_contributed' => 'decimal:2',
        ];
    }

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function leadUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }
}
