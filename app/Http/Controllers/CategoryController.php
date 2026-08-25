<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create($request->validated());

        return response()->json($category, Response::HTTP_CREATED);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return response()->json($category->fresh());
    }

    public function destroy(Request $request, Category $category): Response
    {
        abort_unless($request->user()?->role === 'admin', Response::HTTP_FORBIDDEN);

        $category->delete();

        return response()->noContent();
    }
}
