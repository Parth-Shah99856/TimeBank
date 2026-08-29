<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConvertIdeaToProjectRequest;
use App\Http\Requests\ViewProjectRequest;
use App\Models\Idea;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function storeFromIdea(
        ConvertIdeaToProjectRequest $request,
        Idea $idea,
        ProjectService $projectService,
    ): JsonResponse|RedirectResponse {
        $project = $projectService->convertFromIdea($idea, $request->user());

        if ($request->expectsJson()) {
            return response()->json($project->load(['members.user', 'tasks']), Response::HTTP_CREATED);
        }

        return redirect()->route('projects.show', $project)->with('status', 'Initiative converted to active project successfully.');
    }

    public function show(ViewProjectRequest $request, Project $project): JsonResponse|View
    {
        $project->load(['idea', 'leadUser', 'members.user', 'tasks.assignee']);

        if ($request->expectsJson()) {
            return response()->json($project);
        }

        return view('projects.show', ['project' => $project]);
    }
}
