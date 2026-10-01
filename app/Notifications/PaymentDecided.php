<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Concerns\DatabaseAndMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentDecided extends Notification implements ShouldQueue
{
    use DatabaseAndMail, Queueable;

    public function __construct(
        public Payment $payment,
        public bool $validated,
        public ?string $reason = null,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->validated
            ? __('Paiement :reference validé', ['reference' => $this->payment->reference])
            : __('Paiement :reference refusé', ['reference' => $this->payment->reference]));

        return $this->validated
            ? $mail->line(__('Merci ! Votre paiement de :amount DH est validé, votre service est actif.', ['amount' => $this->payment->amount]))
                ->action(__('Mes factures'), route('dashboard.billing', ['business' => $this->payment->business_id]))
            : $mail->line(__('Motif : :reason', ['reason' => (string) $this->reason]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->validated ? 'payment_validated' : 'payment_rejected',
            'payment_id' => $this->payment->id,
            'reference' => $this->payment->reference,
            'reason' => $this->reason,
        ];
    }
}
