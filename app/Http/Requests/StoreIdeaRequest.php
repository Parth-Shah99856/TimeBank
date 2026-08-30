<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === 'custom' || $value === 'other') {
                        return;
                    }

                    if (! is_numeric($value) || ! Category::query()->where('id', (int) $value)->where('is_active', true)->exists()) {
                        $fail('The selected category is invalid.');
                    }
                },
            ],
            'custom_category' => [
                'required_if:category_id,custom,other',
                'nullable',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\pL\pN\s\-_&+\/().,\'"]+$/u',
            ],
            'title' => ['required', 'string', 'max:255'],
            'mission_statement' => ['required', 'string'],
            'target_hours' => ['required', 'numeric', 'gt:0'],
            'required_skills' => ['nullable', 'array'],
            'required_skills.*' => ['string'],
            'status' => ['sometimes', Rule::in(['open', 'recruiting', 'converted_to_project', 'archived'])],
        ];
    }

    public function messages(): array
    {
        return [
            'custom_category.required_if' => 'Please enter a custom category name.',
            'custom_category.min' => 'Custom category name must be at least 2 characters.',
            'custom_category.max' => 'Custom category name cannot exceed 100 characters.',
            'custom_category.regex' => 'Custom category name contains invalid characters.',
        ];
    }
}
