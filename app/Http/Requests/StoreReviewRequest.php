<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ServiceRequest|null $serviceRequest */
        $serviceRequest = $this->route('service_request');
        $user = $this->user();

        if ($user === null || $serviceRequest === null) {
            return false;
        }

        return in_array($user->id, [$serviceRequest->requester_id, $serviceRequest->provider_id], true);
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
