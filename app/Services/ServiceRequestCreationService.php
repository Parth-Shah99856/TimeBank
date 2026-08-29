<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ServiceRequestCreationService
{
    public function create(User $requester, array $attributes): ServiceRequest
    {
        return DB::transaction(function () use ($requester, $attributes): ServiceRequest {
            $service = Service::query()
                ->whereKey($attributes['service_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $service->is_active) {
                throw new RuntimeException('This service is not currently active.');
            }

            if ($service->user_id === $requester->id) {
                throw new RuntimeException('You cannot request your own service.');
            }

            $totalCredits = bcmul((string) $attributes['estimated_hours'], (string) $service->hourly_rate, 2);

            return ServiceRequest::query()->create([
                'service_id' => $service->id,
                'requester_id' => $requester->id,
                'provider_id' => $service->user_id,
                'category_id' => $service->category_id,
                'title' => $attributes['title'],
                'project_scope' => $attributes['project_scope'],
                'estimated_hours' => $attributes['estimated_hours'],
                'total_credits' => $totalCredits,
                'desired_deadline' => $attributes['desired_deadline'] ?? null,
                'status' => 'pending',
                'completed_at' => null,
            ]);
        });
    }
}
