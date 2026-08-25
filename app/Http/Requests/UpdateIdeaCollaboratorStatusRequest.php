<?php

namespace App\Http\Requests;

use App\Models\IdeaCollaborator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIdeaCollaboratorStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var IdeaCollaborator|null $ideaCollaborator */
        $ideaCollaborator = $this->route('idea_collaborator');

        return $this->user() !== null
            && $ideaCollaborator !== null
            && $this->user()->id === $ideaCollaborator->idea->user_id;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['accepted', 'declined'])],
        ];
    }
}
