<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AdminUserQueryService
{
    public function listAll(): Collection
    {
        return User::query()->orderByDesc('created_at')->get();
    }
}
