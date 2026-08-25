<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'hourly_rate' => ['required', 'numeric', 'gt:0'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
