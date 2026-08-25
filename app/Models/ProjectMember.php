<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'user_id',
        'member_role',
        'hours_logged',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'hours_logged' => 'decimal:2',
            'joined_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
