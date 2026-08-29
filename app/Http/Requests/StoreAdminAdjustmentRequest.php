<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdminAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => [
                'required',
                'numeric',
                'between:-999999.99,999999.99',
                function (string $attribute, mixed $value, callable $fail): void {
                    if ((float) $value === 0.0) {
                        $fail('The amount must not be zero.');
                    }
                },
            ],
            'description' => ['required', 'string', 'max:255'],
        ];
    }
}
