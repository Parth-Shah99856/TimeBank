<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\ServiceRequestStatusChangedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ServiceRequestLifecycleService
{
    public function accept(ServiceRequest $serviceRequest, User $actor): void
    {
        DB::transaction(function () use ($serviceRequest, $actor): void {
            $serviceRequest = $this->lockServiceRequest($serviceRequest);

            if ($actor->id !== $serviceRequest->provider_id) {
                throw new AuthorizationException('Only the provider can accept this service request.');
            }

            $this->transition($serviceRequest, ['pending'], 'accepted');
        });
    }

    public function cancel(ServiceRequest $serviceRequest, User $actor): void
    {
        DB::transaction(function () use ($serviceRequest, $actor): void {
            $serviceRequest = $this->lockServiceRequest($serviceRequest);

            if ($actor->id !== $serviceRequest->requester_id) {
                throw new AuthorizationException('Only the requester can cancel this service request.');
            }

            $this->transition($serviceRequest, ['pending', 'accepted'], 'cancelled');
        });
    }

    public function start(ServiceRequest $serviceRequest, User $actor): void
    {
        DB::transaction(function () use ($serviceRequest, $actor): void {
            $serviceRequest = $this->lockServiceRequest($serviceRequest);

            if (! in_array($actor->id, [$serviceRequest->requester_id, $serviceRequest->provider_id], true)) {
                throw new AuthorizationException('Only service request participants can start this exchange.');
            }

            $this->transition($serviceRequest, ['accepted'], 'in_progress');
        });
    }

    public function dispute(ServiceRequest $serviceRequest, User $actor): void
    {
        DB::transaction(function () use ($serviceRequest, $actor): void {
            $serviceRequest = $this->lockServiceRequest($serviceRequest);

            if (! in_array($actor->id, [$serviceRequest->requester_id, $serviceRequest->provider_id], true)) {
                throw new AuthorizationException('Only service request participants can dispute this exchange.');
            }

            $this->transition($serviceRequest, ['accepted', 'in_progress'], 'disputed');
        });
    }

    private function lockServiceRequest(ServiceRequest $serviceRequest): ServiceRequest
    {
        return ServiceRequest::query()
            ->whereKey($serviceRequest->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function transition(ServiceRequest $serviceRequest, array $allowedFrom, string $to): void
    {
        if (! in_array($serviceRequest->status, $allowedFrom, true)) {
            throw new RuntimeException(sprintf(
                'Invalid service request transition from [%s] to [%s].',
                $serviceRequest->status,
                $to,
            ));
        }

        $serviceRequest->forceFill([
            'status' => $to,
            'completed_at' => $to === 'completed' ? now() : null,
        ])->save();

        $serviceRequest->requester->notify(new ServiceRequestStatusChangedNotification($serviceRequest, $to));
        $serviceRequest->provider->notify(new ServiceRequestStatusChangedNotification($serviceRequest, $to));
    }
}
