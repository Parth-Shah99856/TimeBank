<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class ViewProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Project|null $project */
        $project = $this->route('project');
        $user = $this->user();

        if ($user === null || $project === null) {
            return false;
        }

        return $user->id === $project->lead_user_id
            || $project->members()->where('user_id', $user->id)->exists();
    }

    public function rules(): array
    {
        return [];
    }
}
