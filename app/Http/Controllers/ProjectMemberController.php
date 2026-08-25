<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteProjectMemberRequest;
use App\Http\Requests\StoreProjectMemberRequest;
use App\Http\Requests\UpdateProjectMemberRequest;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\ProjectMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProjectMemberController extends Controller
{
    public function store(
        StoreProjectMemberRequest $request,
        Project $project,
        ProjectMemberService $projectMemberService,
    ): JsonResponse {
        $member = $projectMemberService->addMember($project, $request->validated());

        return response()->json($member, Response::HTTP_CREATED);
    }

    public function update(
        UpdateProjectMemberRequest $request,
        ProjectMember $projectMember,
        ProjectMemberService $projectMemberService,
    ): JsonResponse {
        $member = $projectMemberService->updateMemberRole($projectMember, $request->validated());

        return response()->json($member);
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
