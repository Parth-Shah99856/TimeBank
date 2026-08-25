<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConvertIdeaToProjectRequest;
use App\Http\Requests\ViewProjectRequest;
use App\Models\Idea;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    public function storeFromIdea(
        ConvertIdeaToProjectRequest $request,
        Idea $idea,
        ProjectService $projectService,
    ): JsonResponse {
        $project = $projectService->convertFromIdea($idea, $request->user());

        return response()->json($project->load(['members.user', 'tasks']), Response::HTTP_CREATED);
    }

    public function show(ViewProjectRequest $request, Project $project): JsonResponse
    {
        return response()->json($project->load(['idea', 'leadUser', 'members.user', 'tasks.assignee']));
    }
}
