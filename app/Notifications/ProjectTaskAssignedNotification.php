<?php

namespace App\Notifications;

use App\Models\ProjectTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectTaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ProjectTask $projectTask) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->projectTask->project_id,
            'project_task_id' => $this->projectTask->id,
            'title' => $this->projectTask->title,
            'status' => $this->projectTask->status,
            'order_index' => $this->projectTask->order_index,
        ];
    }
}
