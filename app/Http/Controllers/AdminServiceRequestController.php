<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListServiceRequestsForAdminRequest;
use App\Services\ServiceRequestQueryService;
use Illuminate\Http\JsonResponse;

class AdminServiceRequestController extends Controller
{
    public function index(ListServiceRequestsForAdminRequest $request, ServiceRequestQueryService $queryService): JsonResponse
    {
        return response()->json($queryService->listForAdmin($request->validated('status')));
    }
}
