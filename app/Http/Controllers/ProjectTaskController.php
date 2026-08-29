<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteProjectTaskRequest;
use App\Http\Requests\StoreProjectTaskRequest;
use App\Http\Requests\UpdateProjectTaskRequest;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Services\ProjectTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ProjectTaskController extends Controller
{
    public function store(
        StoreProjectTaskRequest $request,
        Project $project,
        ProjectTaskService $projectTaskService,
    ): JsonResponse|RedirectResponse {
        $task = $projectTaskService->createTask($project, $request->validated());

        if ($request->expectsJson()) {
            return response()->json($task, Response::HTTP_CREATED);
        }

        return redirect()->route('projects.show', $project)->with('status', 'Project task added successfully.');
    }

    public function update(
        UpdateProjectTaskRequest $request,
        ProjectTask $projectTask,
        ProjectTaskService $projectTaskService,
    ): JsonResponse|RedirectResponse {
        $task = $projectTaskService->updateTask($projectTask, $request->validated());

        if ($request->expectsJson()) {
            return response()->json($task);
        }

        return redirect()->route('projects.show', $projectTask->project_id)->with('status', 'Task updated successfully.');
    }

    public function destroy(
        DeleteProjectTaskRequest $request,
        ProjectTask $projectTask,
        ProjectTaskService $projectTaskService,
    ): Response {
        $projectTaskService->deleteTask($projectTask);

        return response()->noContent();
    }
}
