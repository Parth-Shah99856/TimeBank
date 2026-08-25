<?php

namespace App\Notifications;

use App\Models\IdeaCollaborator;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IdeaApplicationReceivedNotification extends Notification
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
            'applicant_id' => $this->ideaCollaborator->user_id,
            'role_offered' => $this->ideaCollaborator->role_offered,
            'hours_pledged' => $this->ideaCollaborator->hours_pledged,
            'status' => $this->ideaCollaborator->status,
        ];
    }
}
