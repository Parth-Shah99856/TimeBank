<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListServiceRequestsForAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'nullable',
                Rule::in(['pending', 'accepted', 'in_progress', 'completed', 'cancelled', 'disputed']),
            ],
        ];
    }
}
