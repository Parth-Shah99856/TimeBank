<?php

namespace App\Http\Controllers;

use App\Services\ServiceRequestQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function index(Request $request, ServiceRequestQueryService $queryService): JsonResponse|View
    {
        if ($request->expectsJson()) {
            return response()->json($queryService->listForUser($request->user()));
        }

        return view('service-requests.index');
    }

    public function create(): View
    {
        return view('service-requests.create');
    }
}
