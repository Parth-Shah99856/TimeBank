<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly ?CarbonInterface $loginTime = null,
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
        $timeString = ($this->loginTime ?? now())->format('F j, Y, g:i A T');

        $mail = (new MailMessage)
            ->subject('Security Alert: Successful Login to TimeBank')
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line('Your TimeBank account was just logged into successfully.')
            ->line('**Login Information:**')
            ->line('• **Time:** ' . $timeString);

        if (! empty($this->ipAddress)) {
            $mail->line('• **IP Address:** ' . $this->ipAddress);
        }

        if (! empty($this->userAgent)) {
            $mail->line('• **Device / Browser:** ' . $this->userAgent);
        }

        return $mail
            ->action('Access TimeBank Dashboard', url('/dashboard'))
            ->line('If this was you, no action is needed.')
            ->line('If you did not initiate this login, please secure your account immediately by resetting your password.');
    }
}
