<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaCollaborator extends Model
{
    protected $fillable = [
        'idea_id',
        'user_id',
        'role_offered',
        'hours_pledged',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hours_pledged' => 'decimal:2',
        ];
    }

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
