<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $verifyUrl = url('/api/email/verify/' . $notifiable->id . '/' . sha1($notifiable->getEmailForVerification()));

        return (new MailMessage)
            ->subject(' Vérifiez votre email — Masasugu')
            ->view('emails.verify-email', [
                'user' => $notifiable,
                'verifyUrl' => $verifyUrl,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
