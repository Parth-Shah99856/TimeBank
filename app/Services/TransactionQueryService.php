<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TransactionQueryService
{
    public function listForUser(User $user): Collection
    {
        return Transaction::query()
            ->with(['serviceRequest', 'fromUser', 'toUser'])
            ->where(function ($query) use ($user): void {
                $query->where('from_user_id', $user->id)
                    ->orWhere('to_user_id', $user->id);
            })
            ->latest('created_at')
            ->get()
            ->each(function (Transaction $transaction) use ($user): void {
                $transaction->setAttribute(
                    'direction',
                    $transaction->to_user_id === $user->id ? 'credit' : 'debit',
                );
            });
    }
}
