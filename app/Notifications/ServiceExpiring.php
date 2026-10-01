<?php

namespace App\Notifications;

use App\Notifications\Concerns\DatabaseAndMail;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Reminder 7 days and 1 day before a plan or a sponsored placement ends.
 */
class ServiceExpiring extends Notification implements ShouldQueue
{
    use DatabaseAndMail, Queueable;

    /**
     * @param  string  $service  "plan" or "placement"
     */
    public function __construct(
        public string $service,
        public int $businessId,
        public string $businessName,
        public CarbonInterface $endsAt,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->service === 'plan'
                ? __('Votre formule pour :business se termine le :date', ['business' => $this->businessName, 'date' => $this->endsAt->format('d/m/Y')])
                : __('Votre emplacement sponsorisé pour :business se termine le :date', ['business' => $this->businessName, 'date' => $this->endsAt->format('d/m/Y')]))
            ->line(__('Renouvelez pour ne pas perdre vos avantages.'))
            ->action(__('Renouveler'), route('dashboard.billing', ['business' => $this->businessId]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->service.'_expiring',
            'business_id' => $this->businessId,
            'ends_at' => $this->endsAt->toIso8601String(),
        ];
    }
}
