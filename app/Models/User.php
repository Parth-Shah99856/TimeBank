<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'headline', 'bio', 'avatar_url', 'time_balance', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'time_balance' => 'decimal:2',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDemoAccount(): bool
    {
        $allowed = config('auth.demo_emails', []);
        return in_array(strtolower($this->email), array_map('strtolower', $allowed), true);
    }

    /**
     * Determine if this account was created prior to the email verification rollout boundary.
     *
     * Pre-existing production accounts created before verification was introduced
     * are treated as verified under application policy without modifying production data.
     */
    public function isLegacyUser(): bool
    {
        $rolloutBoundary = config('auth.verification_rollout_at');

        if (! $rolloutBoundary || ! $this->created_at) {
            return false;
        }

        try {
            $boundaryTimestamp = \Illuminate\Support\Carbon::parse($rolloutBoundary);
            return $this->created_at->lt($boundaryTimestamp);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Determine if the user has verified their email address.
     *
     * An account is considered verified if:
     * 1. Its email_verified_at timestamp is explicitly populated, OR
     * 2. It matches the exact-whitelist demo accounts (DEMO_EMAILS), OR
     * 3. It was created prior to the email verification rollout boundary (legacy compatibility policy).
     */
    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at)
            || $this->isDemoAccount()
            || $this->isLegacyUser();
    }

    public function adminAuditLogs(): HasMany
    {
        return $this->hasMany(AdminAuditLog::class, 'admin_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function requestedServiceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'requester_id');
    }

    public function providedServiceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'provider_id');
    }

    public function outgoingTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_user_id');
    }

    public function incomingTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_user_id');
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }

    public function ideaCollaborations(): HasMany
    {
        return $this->hasMany(IdeaCollaborator::class);
    }

    public function ledProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'lead_user_id');
    }

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function assignedProjectTasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'assigned_to');
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }
}
