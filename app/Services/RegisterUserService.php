<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterUserService
{
    public function register(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $user = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
            ]);

            Transaction::create([
                'transaction_code' => $this->generateTransactionCode(),
                'from_user_id' => null,
                'to_user_id' => $user->id,
                'service_request_id' => null,
                'amount' => '5.00',
                'type' => Transaction::TYPE_SIGNUP_BONUS,
                'description' => 'Signup bonus',
                'created_at' => now(),
            ]);

            return $user;
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
