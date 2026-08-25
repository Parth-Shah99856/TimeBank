<?php

namespace App\Http\Requests;

use App\Models\ProjectMember;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ProjectMember|null $projectMember */
        $projectMember = $this->route('project_member');

        return $this->user() !== null
            && $projectMember !== null
            && $this->user()->id === $projectMember->project->lead_user_id;
    }

    public function rules(): array
    {
        return [
            'member_role' => ['required', 'string', 'max:255'],
        ];
    }
}
