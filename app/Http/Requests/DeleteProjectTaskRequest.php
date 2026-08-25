<?php

namespace App\Http\Requests;

use App\Models\ProjectTask;
use Illuminate\Foundation\Http\FormRequest;

class DeleteProjectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ProjectTask|null $projectTask */
        $projectTask = $this->route('project_task');

        return $this->user() !== null
            && $projectTask !== null
            && $this->user()->id === $projectTask->project->lead_user_id;
    }

    public function rules(): array
    {
        return [];
    }
}
