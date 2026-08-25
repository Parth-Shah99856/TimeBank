<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Notifications\DatabaseNotification;

class MarkNotificationAsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var DatabaseNotification|null $notification */
        $notification = $this->route('notification');
        $user = $this->user();

        if ($user === null || $notification === null) {
            return false;
        }

        return $notification->notifiable_type === User::class
            && (int) $notification->notifiable_id === $user->id;
    }

    public function rules(): array
    {
        return [];
    }
}
