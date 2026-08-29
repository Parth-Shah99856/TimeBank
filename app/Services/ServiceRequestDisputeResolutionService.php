<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ServiceRequestStatusChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ServiceRequestDisputeResolutionService
{
    public function resolve(ServiceRequest $serviceRequest, string $resolution): ServiceRequest
    {
        return DB::transaction(function () use ($serviceRequest, $resolution): ServiceRequest {
            $serviceRequest = ServiceRequest::query()
                ->whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($serviceRequest->status !== 'disputed') {
                throw new RuntimeException('Only disputed service requests can be resolved.');
            }

            if ($resolution === 'cancelled') {
                $serviceRequest->forceFill([
                    'status' => 'cancelled',
                    'completed_at' => null,
                ])->save();

                $serviceRequest->requester->notify(new ServiceRequestStatusChangedNotification($serviceRequest, 'cancelled'));
                $serviceRequest->provider->notify(new ServiceRequestStatusChangedNotification($serviceRequest, 'cancelled'));

                return $serviceRequest;
            }

            if ($serviceRequest->transactions()->where('type', Transaction::TYPE_SERVICE_EXCHANGE)->exists()) {
                throw new RuntimeException('This service request has already been settled.');
            }

            $requester = User::query()->whereKey($serviceRequest->requester_id)->lockForUpdate()->firstOrFail();
            $provider = User::query()->whereKey($serviceRequest->provider_id)->lockForUpdate()->firstOrFail();

            if (bccomp((string) $requester->time_balance, (string) $serviceRequest->total_credits, 2) < 0) {
                throw new RuntimeException('Insufficient time balance to complete this exchange.');
            }

            $requester->forceFill([
                'time_balance' => bcsub((string) $requester->time_balance, (string) $serviceRequest->total_credits, 2),
            ])->save();

            $provider->forceFill([
                'time_balance' => bcadd((string) $provider->time_balance, (string) $serviceRequest->total_credits, 2),
            ])->save();

            Transaction::create([
                'transaction_code' => $this->generateTransactionCode(),
                'from_user_id' => $requester->id,
                'to_user_id' => $provider->id,
                'service_request_id' => $serviceRequest->id,
                'amount' => (string) $serviceRequest->total_credits,
                'type' => Transaction::TYPE_SERVICE_EXCHANGE,
                'description' => 'Dispute resolved: service exchange completed by admin',
                'created_at' => now(),
            ]);

            $serviceRequest->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
            ])->save();

            $serviceRequest->requester->notify(new ServiceRequestStatusChangedNotification($serviceRequest, 'completed'));
            $serviceRequest->provider->notify(new ServiceRequestStatusChangedNotification($serviceRequest, 'completed'));

            return $serviceRequest->fresh();
        });
    }

    private function generateTransactionCode(): string
    {
        do {
            $code = strtoupper(Str::random(32));
        } while (Transaction::query()->where('transaction_code', $code)->exists());

        return $code;
    }
}
