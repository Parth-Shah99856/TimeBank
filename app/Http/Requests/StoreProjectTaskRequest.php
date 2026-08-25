<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Project|null $project */
        $project = $this->route('project');

        return $this->user() !== null
            && $project !== null
            && $this->user()->id === $project->lead_user_id;
    }

    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

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
