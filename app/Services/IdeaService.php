<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Idea;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IdeaService
{
    public function create(User $user, array $attributes): Idea
    {
        return DB::transaction(function () use ($user, $attributes): Idea {
            $categoryId = $attributes['category_id'] ?? null;
            $customCategory = isset($attributes['custom_category']) ? trim((string) $attributes['custom_category']) : '';

            if ($categoryId === 'custom' || $categoryId === 'other' || (! empty($customCategory) && ! is_numeric($categoryId))) {
                $categoryName = $customCategory;
                $existing = Category::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])
                    ->first();

                if ($existing) {
                    if (! $existing->is_active) {
                        $existing->update(['is_active' => true]);
                    }
                    $categoryId = $existing->id;
                } else {
                    $baseSlug = Str::slug($categoryName) ?: 'custom-category';
                    $slug = $baseSlug;
                    $counter = 1;
                    while (Category::query()->where('slug', $slug)->exists()) {
                        $slug = $baseSlug . '-' . $counter++;
                    }

                    $newCategory = Category::query()->create([
                        'name' => $categoryName,
                        'slug' => $slug,
                        'description' => 'Community-proposed initiative category',
                        'icon' => 'lightbulb',
                        'is_active' => true,
                    ]);

                    $categoryId = $newCategory->id;
                }
            }

            return $user->ideas()->create([
                'category_id' => (int) $categoryId,
                'title' => $attributes['title'],
                'mission_statement' => $attributes['mission_statement'],
                'target_hours' => $attributes['target_hours'],
                'required_skills' => $attributes['required_skills'] ?? null,
                'status' => $attributes['status'] ?? 'open',
            ]);
        });
    }

    public function update(Idea $idea, array $attributes): Idea
    {
        return DB::transaction(function () use ($idea, $attributes): Idea {
            $idea->update($attributes);

            return $idea->fresh();
        });
    }

    public function delete(Idea $idea): void
    {
        DB::transaction(function () use ($idea): void {
            $idea->delete();
        });
    }
}
