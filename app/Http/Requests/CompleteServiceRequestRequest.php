<?php

namespace App\Http\Requests;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;

class CompleteServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ServiceRequest|null $serviceRequest */
        $serviceRequest = $this->route('service_request');

        return $this->user() !== null
            && $serviceRequest !== null
            && $this->user()->id === $serviceRequest->requester_id;
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'Please enter the 6-digit confirmation OTP.',
            'otp.digits' => 'The confirmation OTP must be exactly 6 numeric digits.',
        ];
    }
}
