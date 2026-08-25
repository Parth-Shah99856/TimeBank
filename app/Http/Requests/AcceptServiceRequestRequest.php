<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;

class AcceptServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ServiceRequest|null $serviceRequest */
        $serviceRequest = $this->route('service_request');

        return $this->user() !== null
            && $serviceRequest !== null
            && $this->user()->id === $serviceRequest->provider_id;
    }

    public function rules(): array
    {
        return [];
    }
}
