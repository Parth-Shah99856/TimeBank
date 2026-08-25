<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Idea extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'mission_statement',
        'target_hours',
        'required_skills',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_hours' => 'decimal:2',
            'required_skills' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function collaborators(): HasMany
    {
        return $this->hasMany(IdeaCollaborator::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
