<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteIdeaRequest;
use App\Http\Requests\StoreIdeaRequest;
use App\Http\Requests\UpdateIdeaRequest;
use App\Models\Idea;
use App\Services\IdeaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class IdeaController extends Controller
{
    public function index(): JsonResponse
    {
        $ideas = Idea::query()
            ->with(['category', 'user'])
            ->latest()
            ->get();

        return response()->json($ideas);
    }

    public function store(StoreIdeaRequest $request, IdeaService $ideaService): JsonResponse
    {
        $idea = $ideaService->create($request->user(), $request->validated());

        return response()->json($idea, Response::HTTP_CREATED);
    }

    public function update(UpdateIdeaRequest $request, Idea $idea, IdeaService $ideaService): JsonResponse
    {
        $updatedIdea = $ideaService->update($idea, $request->validated());

        return response()->json($updatedIdea);
    }

    public function destroy(DeleteIdeaRequest $request, Idea $idea, IdeaService $ideaService): Response
    {
        $ideaService->delete($idea);

        return response()->noContent();
    }
}
