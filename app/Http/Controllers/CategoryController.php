<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function store(StoreCategoryRequest $request): JsonResponse|RedirectResponse
    {
        $category = Category::query()->create($request->validated());

        if ($request->expectsJson()) {
            return response()->json($category, Response::HTTP_CREATED);
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category domain created successfully.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse|RedirectResponse
    {
        $category->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json($category->fresh());
        }

        return redirect()->route('admin.categories.index')->with('status', 'Category domain updated successfully.');
    }

    public function destroy(Request $request, Category $category): Response
    {
        abort_unless($request->user()?->isAdmin(), Response::HTTP_FORBIDDEN);

        $category->delete();

        return response()->noContent();
    }
}
