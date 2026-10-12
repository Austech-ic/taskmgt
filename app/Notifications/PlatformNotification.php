<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification type for in-app + optional email delivery.
 * Keeps sensitive financial/personal detail out of email bodies: emails link back to the app.
 */
class PlatformNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = null,
        public string $icon = 'bell',
        public bool $sensitive = false,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $mailer = config('mail.default');
        $prefs = $notifiable->notification_prefs ?? [];
        if (! in_array($mailer, ['log', 'array', null], true) && ($prefs['email'] ?? true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title.' · '.setting('site_name'))
            ->greeting('Hey '.$notifiable->firstName().' 👋')
            ->line($this->sensitive ? 'You have a new update on your account.' : $this->body);
        if ($this->url) {
            $mail->action('Open '.setting('site_name'), $this->url);
        }

        return $mail->salutation('— The '.setting('site_name').' crew');
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'url' => $this->url, 'icon' => $this->icon];
    }
}
