<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AdminAdjustmentService
{
    public function adjust(array $attributes): Transaction
    {
        return DB::transaction(function () use ($attributes): Transaction {
            $targetUser = User::query()
                ->whereKey($attributes['user_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (string) $attributes['amount'];
            $isCredit = bccomp($amount, '0.00', 2) > 0;
            $magnitude = $isCredit ? $amount : bcmul($amount, '-1', 2);

            if (! $isCredit && bccomp((string) $targetUser->time_balance, $magnitude, 2) < 0) {
                throw new RuntimeException('Insufficient balance for this adjustment.');
            }

            $targetUser->forceFill([
                'time_balance' => bcadd((string) $targetUser->time_balance, $amount, 2),
            ])->save();

            return Transaction::create([
                'transaction_code' => $this->generateTransactionCode(),
                'from_user_id' => $isCredit ? null : $targetUser->id,
                'to_user_id' => $isCredit ? $targetUser->id : null,
                'service_request_id' => null,
                'amount' => $magnitude,
                'type' => Transaction::TYPE_ADMIN_ADJUSTMENT,
                'description' => $attributes['description'],
                'created_at' => now(),
            ]);
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
