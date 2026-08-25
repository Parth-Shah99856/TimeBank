<?php

namespace App\Notifications;

use App\Models\ProjectMember;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectMemberAddedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ProjectMember $projectMember) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->projectMember->project_id,
            'project_member_id' => $this->projectMember->id,
            'member_role' => $this->projectMember->member_role,
        ];
    }
}
