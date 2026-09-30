<?php

namespace App\Notifications;

use App\Notifications\Concerns\DatabaseAndMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when moderation rejects or hides something a user wrote,
 * always with the reason (spec §10).
 */
class ContentRejected extends Notification implements ShouldQueue
{
    use DatabaseAndMail, Queueable;

    /**
     * @param  string  $contentType  review | reply | business | photo
     */
    public function __construct(
        public string $contentType,
        public int $contentId,
        public string $reason,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Votre contenu n’a pas été publié'))
            ->line(__('Notre équipe de modération n’a pas publié votre :type.', ['type' => $this->typeLabel()]))
            ->line(__('Motif : :reason', ['reason' => $this->reason]))
            ->action(__('Règles des avis'), route('page.show', ['page' => 'regles-des-avis']));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'content_rejected',
            'content_type' => $this->contentType,
            'content_id' => $this->contentId,
            'reason' => $this->reason,
        ];
    }

    private function typeLabel(): string
    {
        return match ($this->contentType) {
            'review' => __('avis'),
            'reply' => __('réponse'),
            'business' => __('fiche'),
            default => __('photo'),
        };
    }
}
