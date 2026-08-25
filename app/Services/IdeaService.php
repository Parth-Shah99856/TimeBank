<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class IdeaService
{
    public function create(User $user, array $attributes): Idea
    {
        return DB::transaction(function () use ($user, $attributes): Idea {
            return $user->ideas()->create([
                'category_id' => $attributes['category_id'],
                'title' => $attributes['title'],
                'mission_statement' => $attributes['mission_statement'],
                'target_hours' => $attributes['target_hours'],
                'required_skills' => $attributes['required_skills'] ?? null,
                'status' => $attributes['status'] ?? 'open',
            ]);
        });
    }

    public function update(Idea $idea, array $attributes): Idea
    {
        return DB::transaction(function () use ($idea, $attributes): Idea {
            $idea->update($attributes);

            return $idea->fresh();
        });
    }

    public function delete(Idea $idea): void
    {
        DB::transaction(function () use ($idea): void {
            $idea->delete();
        });
    }
}
