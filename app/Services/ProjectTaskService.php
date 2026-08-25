<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Notifications\ProjectTaskAssignedNotification;
use Illuminate\Support\Facades\DB;

class ProjectTaskService
{
    public function createTask(Project $project, array $attributes): ProjectTask
    {
        return DB::transaction(function () use ($project, $attributes): ProjectTask {
            $project = Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();

            $task = $project->tasks()->create($attributes);

            if ($task->assigned_to !== null) {
                $task->assignee->notify(new ProjectTaskAssignedNotification($task));
            }

            return $task;
        });
    }

    public function updateTask(ProjectTask $projectTask, array $attributes): ProjectTask
    {
        return DB::transaction(function () use ($projectTask, $attributes): ProjectTask {
            $projectTask = ProjectTask::query()->whereKey($projectTask->id)->lockForUpdate()->firstOrFail();
            $originalAssignedTo = $projectTask->assigned_to;
            $projectTask->update($attributes);
            $projectTask = $projectTask->fresh();

            if ($projectTask->assigned_to !== null && $projectTask->assigned_to !== $originalAssignedTo) {
                $projectTask->assignee->notify(new ProjectTaskAssignedNotification($projectTask));
            }

            return $projectTask;
        });
    }

    public function deleteTask(ProjectTask $projectTask): void
    {
        DB::transaction(function () use ($projectTask): void {
            $projectTask = ProjectTask::query()->whereKey($projectTask->id)->lockForUpdate()->firstOrFail();
            $projectTask->delete();
        });
    }
}
