<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteProjectMemberRequest;
use App\Http\Requests\StoreProjectMemberRequest;
use App\Http\Requests\UpdateProjectMemberRequest;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\ProjectMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ProjectMemberController extends Controller
{
    public function store(
        StoreProjectMemberRequest $request,
        Project $project,
        ProjectMemberService $projectMemberService,
    ): JsonResponse|RedirectResponse {
        $member = $projectMemberService->addMember($project, $request->validated());

        if ($request->expectsJson()) {
            return response()->json($member, Response::HTTP_CREATED);
        }

        return redirect()->route('projects.show', $project)->with('status', 'Project contributor added successfully.');
    }

    public function update(
        UpdateProjectMemberRequest $request,
        ProjectMember $projectMember,
        ProjectMemberService $projectMemberService,
    ): JsonResponse|RedirectResponse {
        $member = $projectMemberService->updateMemberRole($projectMember, $request->validated());

        if ($request->expectsJson()) {
            return response()->json($member);
        }

        return redirect()->route('projects.show', $projectMember->project_id)->with('status', 'Member role updated successfully.');
    }

    public function destroy(
        DeleteProjectMemberRequest $request,
        ProjectMember $projectMember,
        ProjectMemberService $projectMemberService,
    ): Response {
        $projectMemberService->removeMember($projectMember);

        return response()->noContent();
    }
}
