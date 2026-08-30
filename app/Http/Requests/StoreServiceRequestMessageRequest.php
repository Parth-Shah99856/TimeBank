<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ServiceRequest|null $serviceRequest */
        $serviceRequest = $this->route('service_request');
        $user = $this->user();

        if (! $user || ! $serviceRequest) {
            return false;
        }

        return $user->id === $serviceRequest->requester_id || $user->id === $serviceRequest->provider_id;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Message content cannot be empty.',
            'content.min' => 'Message must contain at least 1 character.',
            'content.max' => 'Message cannot exceed 2000 characters.',
        ];
    }
}
