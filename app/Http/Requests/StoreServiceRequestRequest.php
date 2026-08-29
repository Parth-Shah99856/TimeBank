<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'project_scope' => ['required', 'string'],
            'estimated_hours' => ['required', 'numeric', 'gt:0', 'max:999.99'],
            'desired_deadline' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
