<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ServiceRequestQueryService
{
    public function listForUser(User $user): Collection
    {
        return ServiceRequest::query()
            ->with(['service', 'requester', 'provider', 'category'])
            ->where(function ($query) use ($user): void {
                $query->where('requester_id', $user->id)
                    ->orWhere('provider_id', $user->id);
            })
            ->latest()
            ->get()
            ->each(function (ServiceRequest $serviceRequest) use ($user): void {
                $serviceRequest->setAttribute(
                    'viewer_role',
                    $serviceRequest->requester_id === $user->id ? 'requester' : 'provider',
                );
            });
    }

    public function listForAdmin(?string $status): Collection
    {
        return ServiceRequest::query()
            ->with(['service', 'requester', 'provider', 'category'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();
    }
}
