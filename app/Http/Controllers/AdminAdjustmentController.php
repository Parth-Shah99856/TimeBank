<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminAdjustmentRequest;
use App\Services\AdminAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AdminAdjustmentController extends Controller
{
    public function store(
        StoreAdminAdjustmentRequest $request,
        AdminAdjustmentService $adjustmentService,
    ): JsonResponse {
        $transaction = $adjustmentService->adjust($request->validated());

        return response()->json($transaction, Response::HTTP_CREATED);
    }
}
