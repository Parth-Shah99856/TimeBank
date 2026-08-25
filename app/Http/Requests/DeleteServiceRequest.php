<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;

class DeleteServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Service|null $service */
        $service = $this->route('service');

        return $this->user() !== null
            && $service !== null
            && $this->user()->id === $service->user_id;
    }

    public function rules(): array
    {
        return [];
    }
}
