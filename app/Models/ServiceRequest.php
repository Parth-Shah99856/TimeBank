<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRequest extends Model
{
    protected $fillable = [
        'service_id',
        'requester_id',
        'provider_id',
        'category_id',
        'title',
        'project_scope',
        'estimated_hours',
        'total_credits',
        'desired_deadline',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_hours' => 'decimal:2',
            'total_credits' => 'decimal:2',
            'desired_deadline' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function otps(): HasMany
    {
        return $this->hasMany(ServiceRequestOtp::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ServiceRequestMessage::class);
    }

    public function unreadMessagesCountFor(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}
