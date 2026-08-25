<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIdeaCollaboratorRequest;
use App\Http\Requests\UpdateIdeaCollaboratorStatusRequest;
use App\Models\Idea;
use App\Models\IdeaCollaborator;
use App\Services\IdeaCollaboratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class IdeaCollaboratorController extends Controller
{
    public function store(
        StoreIdeaCollaboratorRequest $request,
        Idea $idea,
        IdeaCollaboratorService $ideaCollaboratorService,
    ): JsonResponse {
        $collaborator = $ideaCollaboratorService->apply($idea, $request->user(), $request->validated());

        return response()->json($collaborator, Response::HTTP_CREATED);
    }

    public function update(
        UpdateIdeaCollaboratorStatusRequest $request,
        IdeaCollaborator $ideaCollaborator,
        IdeaCollaboratorService $ideaCollaboratorService,
    ): JsonResponse {
        $updatedCollaborator = $ideaCollaboratorService->updateStatus($ideaCollaborator, $request->validated('status'));

        return response()->json($updatedCollaborator);
    }
}
