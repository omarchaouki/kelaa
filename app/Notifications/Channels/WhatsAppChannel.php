<?php

namespace App\Notifications\Channels;

use App\Contracts\WhatsAppMessenger;
use Illuminate\Notifications\Notification;

/**
 * Notification channel for a WhatsApp Business API provider.
 * Not active in v1: notifications list it only when the user opted in and a
 * WhatsAppMessenger implementation is bound.
 */
class WhatsAppChannel
{
    public function __construct(
        private readonly WhatsAppMessenger $messenger,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $phone = $notifiable->routeNotificationFor('whatsapp', $notification);

        if (! is_string($phone) || $phone === '') {
            return;
        }

        /** @var array{template: string, parameters: list<string>} $message */
        $message = $notification->toWhatsApp($notifiable);

        $this->messenger->sendTemplate($phone, $message['template'], $message['parameters']);
    }
}
