<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectMemberAddedNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProjectService
{
    public function convertFromIdea(Idea $idea, User $user): Project
    {
        return DB::transaction(function () use ($idea, $user): Project {
            $idea = Idea::query()->whereKey($idea->id)->lockForUpdate()->firstOrFail();

            if ($idea->user_id !== $user->id) {
                throw new RuntimeException('Only the idea owner can convert this idea into a project.');
            }

            if ($idea->status !== 'recruiting') {
                throw new RuntimeException('Only recruiting ideas can be converted into projects.');
            }

            if ($idea->projects()->exists()) {
                throw new RuntimeException('This idea has already been converted into a project.');
            }

            $project = Project::query()->create([
                'idea_id' => $idea->id,
                'lead_user_id' => $user->id,
                'category_id' => $idea->category_id,
                'title' => $idea->title,
                'description' => $idea->mission_statement,
                'target_hours' => $idea->target_hours,
                'hours_contributed' => '0.00',
                'status' => 'planning',
            ]);

            $leadMember = $project->members()->create([
                'user_id' => $user->id,
                'member_role' => 'Lead',
                'hours_logged' => '0.00',
                'joined_at' => now(),
            ]);

            $acceptedCollaborators = $idea->collaborators()
                ->where('status', 'accepted')
                ->get();

            foreach ($acceptedCollaborators as $collaborator) {
                $member = $project->members()->firstOrCreate(
                    ['user_id' => $collaborator->user_id],
                    [
                        'member_role' => $collaborator->role_offered ?: 'Contributor',
                        'hours_logged' => '0.00',
                        'joined_at' => now(),
                    ],
                );

                if ($member->wasRecentlyCreated) {
                    $member->user->notify(new ProjectMemberAddedNotification($member));
                }
            }

            $idea->update([
                'status' => 'converted_to_project',
            ]);

            return $project->fresh();
        });
    }
}
