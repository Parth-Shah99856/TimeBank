<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Service|null $service */
        $service = $this->route('service');

        return $this->user() !== null
            && $service !== null
            && $this->user()->id === $service->user_id;
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
            'is_active' => ['required', 'boolean'],
        ];
    }
}
