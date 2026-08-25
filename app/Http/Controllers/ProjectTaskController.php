<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteProjectTaskRequest;
use App\Http\Requests\StoreProjectTaskRequest;
use App\Http\Requests\UpdateProjectTaskRequest;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Services\ProjectTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProjectTaskController extends Controller
{
    public function store(
        StoreProjectTaskRequest $request,
        Project $project,
        ProjectTaskService $projectTaskService,
    ): JsonResponse {
        $task = $projectTaskService->createTask($project, $request->validated());

        return response()->json($task, Response::HTTP_CREATED);
    }

    public function update(
        UpdateProjectTaskRequest $request,
        ProjectTask $projectTask,
        ProjectTaskService $projectTaskService,
    ): JsonResponse {
        $task = $projectTaskService->updateTask($projectTask, $request->validated());

        return response()->json($task);
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
