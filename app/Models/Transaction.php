<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Transaction extends Model
{
    public const TYPE_SIGNUP_BONUS = 'signup_bonus';
    public const TYPE_SERVICE_EXCHANGE = 'service_exchange';
    public const TYPE_PROJECT_REWARD = 'project_reward';
    public const TYPE_ADMIN_ADJUSTMENT = 'admin_adjustment';

    public $timestamps = false;

    protected $fillable = [
        'transaction_code',
        'from_user_id',
        'to_user_id',
        'service_request_id',
        'amount',
        'type',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction): void {
            if (bccomp((string) $transaction->amount, '0.00', 2) <= 0) {
                throw new LogicException('Transaction amount must be positive.');
            }
        });

        static::updating(function (): void {
            throw new LogicException('Transactions are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new LogicException('Transactions are append-only and cannot be deleted.');
        });
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
