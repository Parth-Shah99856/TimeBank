<?php

namespace App\Notifications;

use App\Models\IdeaCollaborator;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IdeaApplicationStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly IdeaCollaborator $ideaCollaborator) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'idea_id' => $this->ideaCollaborator->idea_id,
            'idea_collaborator_id' => $this->ideaCollaborator->id,
            'status' => $this->ideaCollaborator->status,
            'role_offered' => $this->ideaCollaborator->role_offered,
        ];
    }
}
