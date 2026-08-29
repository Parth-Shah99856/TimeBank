<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteIdeaRequest;
use App\Http\Requests\StoreIdeaRequest;
use App\Http\Requests\UpdateIdeaRequest;
use App\Models\Idea;
use App\Services\IdeaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class IdeaController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $ideas = Idea::query()
            ->with(['category', 'user'])
            ->latest()
            ->get();

        if ($request->expectsJson()) {
            return response()->json($ideas);
        }

        return view('ideas.index');
    }

    public function create(): View
    {
        return view('ideas.create');
    }

    public function show(Request $request, Idea $idea): JsonResponse|View
    {
        $idea->load(['category', 'user', 'collaborators.user', 'projects']);

        if ($request->expectsJson()) {
            return response()->json($idea);
        }

        return view('ideas.show', ['idea' => $idea]);
    }

    public function store(StoreIdeaRequest $request, IdeaService $ideaService): JsonResponse|RedirectResponse
    {
        $idea = $ideaService->create($request->user(), $request->validated());

        if ($request->expectsJson()) {
            return response()->json($idea, Response::HTTP_CREATED);
        }

        return redirect()->route('ideas.show', $idea)->with('status', 'Initiative published to IdeaVault successfully.');
    }

    public function update(UpdateIdeaRequest $request, Idea $idea, IdeaService $ideaService): JsonResponse|RedirectResponse
    {
        $updatedIdea = $ideaService->update($idea, $request->validated());

        if ($request->expectsJson()) {
            return response()->json($updatedIdea);
        }

        return redirect()->route('ideas.show', $updatedIdea)->with('status', 'Initiative updated successfully.');
    }

    public function destroy(DeleteIdeaRequest $request, Idea $idea, IdeaService $ideaService): Response
    {
        $ideaService->delete($idea);

        return response()->noContent();
    }
}
