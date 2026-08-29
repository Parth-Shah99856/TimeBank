<?php

namespace App\Http\Controllers;

use App\Services\TransactionQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request, TransactionQueryService $queryService): JsonResponse|View
    {
        if ($request->expectsJson()) {
            return response()->json($queryService->listForUser($request->user()));
        }

        return view('transactions.index');
    }
}
