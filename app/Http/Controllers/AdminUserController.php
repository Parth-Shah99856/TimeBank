<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListUsersRequest;
use App\Services\AdminUserQueryService;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function index(ListUsersRequest $request, AdminUserQueryService $queryService): JsonResponse
    {
        return response()->json($queryService->listAll());
    }
}
