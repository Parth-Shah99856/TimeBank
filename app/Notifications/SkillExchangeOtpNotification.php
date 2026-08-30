<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SkillExchangeOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ServiceRequest $serviceRequest,
        public readonly string $otp,
        public readonly CarbonInterface $expiresAt,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $credits = number_format((float) ($this->serviceRequest->total_credits ?? $this->serviceRequest->estimated_hours ?? 0), 2);
        $providerName = $this->serviceRequest->provider ? $this->serviceRequest->provider->name : 'Skill Provider';

        return (new MailMessage)
            ->subject('TimeBank Skill Exchange Authorization Code: ' . $this->otp)
            ->greeting('Hello ' . ($notifiable->name ?? 'Architect') . ',')
            ->line('You have requested to confirm and complete the skill exchange for: **' . $this->serviceRequest->title . '**.')
            ->line('**Exchange Summary:**')
            ->line('• **Time Credits to Transfer:** ' . $credits . ' TC')
            ->line('• **Provider:** ' . $providerName)
            ->line('Please use the following 6-digit one-time authorization code to verify and release credits:')
            ->line('### **' . $this->otp . '**')
            ->line('This code will expire in 15 minutes (at ' . $this->expiresAt->format('g:i A T') . ').')
            ->line('If you did not initiate this completion, please contact the provider or dispute the exchange immediately.');
    }
}
