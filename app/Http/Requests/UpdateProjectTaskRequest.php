<?php

namespace App\Http\Requests;

use App\Models\ProjectTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectTaskRequest extends FormRequest
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
        /** @var ProjectTask $projectTask */
        $projectTask = $this->route('project_task');
        $project = $projectTask->project;

        return [
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('project_members', 'user_id')->where('project_id', $project->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_hours' => ['required', 'numeric', 'gte:0'],
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed'])],
            'order_index' => ['required', 'integer', 'min:0'],
        ];
    }
}
