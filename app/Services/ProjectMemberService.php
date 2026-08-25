<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Notifications\ProjectMemberAddedNotification;
use App\Notifications\ProjectMemberRoleChangedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProjectMemberService
{
    public function addMember(Project $project, array $attributes): ProjectMember
    {
        return DB::transaction(function () use ($project, $attributes): ProjectMember {
            $project = Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();

            if ($project->members()->where('user_id', $attributes['user_id'])->exists()) {
                throw new RuntimeException('This user is already a project member.');
            }

            $member = $project->members()->create([
                'user_id' => $attributes['user_id'],
                'member_role' => $attributes['member_role'] ?? 'Contributor',
                'hours_logged' => '0.00',
                'joined_at' => now(),
            ]);

            $member->user->notify(new ProjectMemberAddedNotification($member));

            return $member;
        });
    }

    public function updateMemberRole(ProjectMember $projectMember, array $attributes): ProjectMember
    {
        return DB::transaction(function () use ($projectMember, $attributes): ProjectMember {
            $projectMember = ProjectMember::query()
                ->with('project', 'user')
                ->whereKey($projectMember->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $projectMember->project->members()->whereKey($projectMember->id)->exists()) {
                throw new RuntimeException('The specified project member does not belong to this project.');
            }

            $previousRole = $projectMember->member_role;
            $newRole = $attributes['member_role'];

            if ($previousRole === $newRole) {
                return $projectMember;
            }

            $projectMember->forceFill([
                'member_role' => $newRole,
            ])->save();

            $projectMember->user->notify(new ProjectMemberRoleChangedNotification($projectMember->fresh(), $previousRole));

            return $projectMember->fresh();
        });
    }

    public function removeMember(ProjectMember $projectMember): void
    {
        DB::transaction(function () use ($projectMember): void {
            $projectMember = ProjectMember::query()->whereKey($projectMember->id)->lockForUpdate()->firstOrFail();

            if ($projectMember->member_role === 'Lead') {
                throw new RuntimeException('The project lead cannot be removed as a member.');
            }

            $projectMember->delete();
        });
    }
}
