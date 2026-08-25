<?php

namespace App\Http\Requests;

use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Idea|null $idea */
        $idea = $this->route('idea');

        return $this->user() !== null
            && $idea !== null
            && $this->user()->id === $idea->user_id;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'mission_statement' => ['required', 'string'],
            'target_hours' => ['required', 'numeric', 'gt:0'],
            'required_skills' => ['nullable', 'array'],
            'required_skills.*' => ['string'],
            'status' => ['required', Rule::in(['open', 'recruiting', 'converted_to_project', 'archived'])],
        ];
    }
}
