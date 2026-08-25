<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin Controller',
                'email' => 'admin@timebank.local',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'headline' => 'TimeBank Platform Administrator',
                'bio' => 'System administrator managing TimeBank operations and category moderation.',
                'time_balance' => 5.00,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Elena Rostova',
                'email' => 'elena@timebank.local',
                'password' => Hash::make('password'),
                'role' => 'user',
                'headline' => 'Senior Systems Engineer & Full-Stack Architect',
                'bio' => 'Specializing in distributed systems, high-performance web platforms, and database optimization.',
                'time_balance' => 5.00,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Marcus Chen',
                'email' => 'marcus@timebank.local',
                'password' => Hash::make('password'),
                'role' => 'user',
                'headline' => 'UI/UX Designer & Design Systems Lead',
                'bio' => 'Passionate about crafting intuitive, futuristic interfaces and accessible design tokens.',
                'time_balance' => 5.00,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sarah Jenkins',
                'email' => 'sarah@timebank.local',
                'password' => Hash::make('password'),
                'role' => 'user',
                'headline' => 'Data Scientist & Machine Learning Specialist',
                'bio' => 'Building predictive analytics models, data extraction pipelines, and automated NLP tools.',
                'time_balance' => 5.00,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Record signup bonus in immutable transaction ledger if not already recorded
            $existingBonus = Transaction::where('to_user_id', $user->id)
                ->where('type', Transaction::TYPE_SIGNUP_BONUS)
                ->first();

            if (! $existingBonus) {
                Transaction::create([
                    'transaction_code' => 'TX-BONUS-' . strtoupper(Str::random(8)),
                    'from_user_id' => null,
                    'to_user_id' => $user->id,
                    'service_request_id' => null,
                    'amount' => 5.00,
                    'type' => Transaction::TYPE_SIGNUP_BONUS,
                    'description' => 'Initial signup bonus',
                    'created_at' => now(),
                ]);
            }
        }
    }
}
