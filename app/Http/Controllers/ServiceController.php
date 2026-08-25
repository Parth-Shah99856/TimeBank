<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteServiceRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        $services = Service::query()
            ->with(['category', 'user'])
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->latest()
            ->get();

        return response()->json($services);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = $request->user()->services()->create([
            'category_id' => $request->validated('category_id'),
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'hourly_rate' => $request->validated('hourly_rate'),
            'tags' => $request->validated('tags'),
            'is_active' => $request->validated('is_active', true),
        ]);

        return response()->json($service->fresh(), Response::HTTP_CREATED);
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $service->update($request->validated());

        return response()->json($service->fresh());
    }

    public function destroy(DeleteServiceRequest $request, Service $service): Response
    {
        $service->delete();

        return response()->noContent();
    }
}
