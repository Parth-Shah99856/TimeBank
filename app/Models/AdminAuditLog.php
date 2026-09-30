<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lightweight append-only audit log for admin actions.
 */
class AdminAuditLog extends Model
{
    protected $fillable = [
        'admin_id',
        'admin_name',
        'action',
        'target_type',
        'target_id',
        'target_label',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Record an admin action safely.
     */
    public static function record(
        User $admin,
        string $action,
        string $targetType,
        int $targetId,
        string $targetLabel,
        array $meta = []
    ): self {
        return static::create([
            'admin_id'     => $admin->id,
            'admin_name'   => $admin->name,
            'action'       => $action,
            'target_type'  => $targetType,
            'target_id'    => $targetId,
            'target_label' => $targetLabel,
            'meta'         => empty($meta) ? null : $meta,
        ]);
    }
}