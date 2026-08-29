<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteServiceRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $services = Service::query()
            ->with(['category', 'user'])
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->latest()
            ->get();

        if ($request->expectsJson()) {
            return response()->json($services);
        }

        return view('services.index');
    }

    public function create(): View
    {
        return view('services.create');
    }

    public function show(Request $request, Service $service): JsonResponse|View
    {
        $service->load(['category', 'user']);

        if ($request->expectsJson()) {
            return response()->json($service);
        }

        return view('services.show', ['service' => $service]);
    }

    public function store(StoreServiceRequest $request): JsonResponse|RedirectResponse
    {
        $service = $request->user()->services()->create([
            'category_id' => $request->validated('category_id'),
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'hourly_rate' => $request->validated('hourly_rate'),
            'tags' => $request->validated('tags'),
            'is_active' => $request->validated('is_active', true),
        ]);

        if ($request->expectsJson()) {
            return response()->json($service->fresh(), Response::HTTP_CREATED);
        }

        return redirect()->route('services.show', $service)->with('status', 'Skill offering published successfully.');
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse|RedirectResponse
    {
        $service->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json($service->fresh());
        }

        return redirect()->route('services.show', $service)->with('status', 'Skill offering updated successfully.');
    }

    public function destroy(DeleteServiceRequest $request, Service $service): Response
    {
        $service->delete();

        return response()->noContent();
    }
}
