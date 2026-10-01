<?php

namespace App\Notifications;

use App\Models\BusinessClaim;
use App\Notifications\Concerns\DatabaseAndMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimDecided extends Notification implements ShouldQueue
{
    use DatabaseAndMail, Queueable;

    public function __construct(
        public BusinessClaim $claim,
        public bool $approved,
        public ?string $reason = null,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $business = $this->claim->loadMissing('business')->business;

        $mail = (new MailMessage)->subject($this->approved
            ? __('Votre fiche :business est à vous', ['business' => $business->name])
            : __('Votre demande pour :business n’a pas été acceptée', ['business' => $business->name]));

        return $this->approved
            ? $mail->line(__('Vous pouvez maintenant répondre aux avis et mettre à jour votre fiche.'))
                ->action(__('Mon espace professionnel'), route('dashboard'))
            : $mail->line(__('Motif : :reason', ['reason' => (string) $this->reason]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->approved ? 'claim_approved' : 'claim_rejected',
            'claim_id' => $this->claim->id,
            'business_id' => $this->claim->business_id,
            'reason' => $this->reason,
        ];
    }
}
