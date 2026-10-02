<?php

namespace App\Notifications;

use App\Notifications\Concerns\DatabaseAndMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the person who submitted a listing whether it was published or
 * declined (with the reason).
 */
class BusinessRequestDecided extends Notification implements ShouldQueue
{
    use DatabaseAndMail, Queueable;

    public function __construct(
        public int $businessId,
        public string $businessName,
        public bool $published,
        public ?string $url = null,
        public ?string $reason = null,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = new MailMessage;

        if ($this->published) {
            return $mail->subject(__('« :name » est en ligne', ['name' => $this->businessName]))
                ->line(__('Bonne nouvelle : l’établissement que vous avez proposé a été vérifié et publié.'))
                ->action(__('Voir la fiche'), (string) $this->url);
        }

        return $mail->subject(__('« :name » n’a pas été publié', ['name' => $this->businessName]))
            ->line(__('Notre équipe n’a pas pu publier l’établissement que vous avez proposé.'))
            ->line(__('Motif : :reason', ['reason' => (string) $this->reason]))
            ->action(__('Mes demandes'), route('dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->published ? 'business_request_published' : 'business_request_declined',
            'business_id' => $this->businessId,
            'reason' => $this->reason,
        ];
    }
}
