<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIdeaCollaboratorRequest;
use App\Http\Requests\UpdateIdeaCollaboratorStatusRequest;
use App\Models\Idea;
use App\Models\IdeaCollaborator;
use App\Services\IdeaCollaboratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class IdeaCollaboratorController extends Controller
{
    public function store(
        StoreIdeaCollaboratorRequest $request,
        Idea $idea,
        IdeaCollaboratorService $ideaCollaboratorService,
    ): JsonResponse|RedirectResponse {
        $collaborator = $ideaCollaboratorService->apply($idea, $request->user(), $request->validated());

        if ($request->expectsJson()) {
            return response()->json($collaborator, Response::HTTP_CREATED);
        }

        return redirect()->route('ideas.show', $idea)->with('status', 'Application submitted to project lead successfully.');
    }

    public function update(
        UpdateIdeaCollaboratorStatusRequest $request,
        IdeaCollaborator $ideaCollaborator,
        IdeaCollaboratorService $ideaCollaboratorService,
    ): JsonResponse|RedirectResponse {
        $updatedCollaborator = $ideaCollaboratorService->updateStatus($ideaCollaborator, $request->validated('status'));

        if ($request->expectsJson()) {
            return response()->json($updatedCollaborator);
        }

        return redirect()->route('ideas.show', $ideaCollaborator->idea_id)->with('status', 'Collaborator application status updated.');
    }
}
