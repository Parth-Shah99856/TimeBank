<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\IdeaCollaborator;
use App\Models\User;
use App\Notifications\IdeaApplicationReceivedNotification;
use App\Notifications\IdeaApplicationStatusNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IdeaCollaboratorService
{
    public function apply(Idea $idea, User $user, array $attributes): IdeaCollaborator
    {
        return DB::transaction(function () use ($idea, $user, $attributes): IdeaCollaborator {
            $idea = Idea::query()->whereKey($idea->id)->lockForUpdate()->firstOrFail();

            if ($idea->collaborators()->where('user_id', $user->id)->exists()) {
                throw new RuntimeException('You have already applied to this idea.');
            }

            $collaborator = $idea->collaborators()->create([
                'user_id' => $user->id,
                'role_offered' => $attributes['role_offered'] ?? null,
                'hours_pledged' => $attributes['hours_pledged'],
                'status' => 'pending',
            ]);

            $idea->user->notify(new IdeaApplicationReceivedNotification($collaborator));

            return $collaborator;
        });
    }

    public function updateStatus(IdeaCollaborator $ideaCollaborator, string $status): IdeaCollaborator
    {
        return DB::transaction(function () use ($ideaCollaborator, $status): IdeaCollaborator {
            $ideaCollaborator = IdeaCollaborator::query()->whereKey($ideaCollaborator->id)->lockForUpdate()->firstOrFail();
            $ideaCollaborator->update([
                'status' => $status,
            ]);

            $ideaCollaborator = $ideaCollaborator->fresh();
            $ideaCollaborator->user->notify(new IdeaApplicationStatusNotification($ideaCollaborator));

            return $ideaCollaborator;
        });
    }
}
