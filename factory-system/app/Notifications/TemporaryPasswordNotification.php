<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemporaryPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $temporaryPassword)
    {
        $this->onQueue('notifications')->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $notifiable->email ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.temporary_password.subject'))
            ->view('emails.temporary-password', [
                'login_url' => route('login'),
                'name' => $notifiable->name,
                'temporary_password' => $this->temporaryPassword,
            ]);
    }
}
