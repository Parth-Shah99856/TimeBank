<?php

namespace App\Http\Requests;

use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;

class ConvertIdeaToProjectRequest extends FormRequest
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
        return [];
    }
}
