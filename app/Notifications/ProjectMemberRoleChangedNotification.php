<?php

namespace App\Notifications;

use App\Models\ProjectMember;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectMemberRoleChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ProjectMember $projectMember,
        private readonly string $previousRole,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->projectMember->project_id,
            'project_member_id' => $this->projectMember->id,
            'previous_role' => $this->previousRole,
            'member_role' => $this->projectMember->member_role,
        ];
    }
}
