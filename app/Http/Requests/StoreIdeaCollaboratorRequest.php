<?php

namespace App\Http\Requests;

use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;

class StoreIdeaCollaboratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Idea|null $idea */
        $idea = $this->route('idea');

        return $this->user() !== null
            && $idea !== null
            && $this->user()->id !== $idea->user_id;
    }

    public function rules(): array
    {
        return [
            'role_offered' => ['nullable', 'string', 'max:255'],
            'hours_pledged' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'hours_pledged.required' => 'Please specify the hours you pledge to contribute to this initiative.',
            'hours_pledged.numeric' => 'Pledged hours must be a numerical value.',
            'hours_pledged.gt' => 'Pledged hours must be greater than zero.',
        ];
    }
}
