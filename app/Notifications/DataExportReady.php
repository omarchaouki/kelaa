<?php

namespace App\Notifications;

use App\Notifications\Concerns\DatabaseAndMail;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * The link is signed, expires with the file, and only works for the logged-in
 * owner of the export (checked again by the download controller).
 */
class DataExportReady extends Notification implements ShouldQueue
{
    use DatabaseAndMail, Queueable;

    public function __construct(
        public string $token,
        public CarbonInterface $expiresAt,
    ) {}

    public function url(object $notifiable): string
    {
        return URL::temporarySignedRoute('account.export.download', $this->expiresAt, [
            'locale' => $notifiable->locale ?? config('directory.default_locale'),
            'token' => $this->token,
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Vos données sont prêtes'))
            ->line(__('Le fichier contenant vos données est disponible jusqu’au :date.', ['date' => $this->expiresAt->format('d/m/Y')]))
            ->action(__('Télécharger mes données'), $this->url($notifiable))
            ->line(__('Si vous n’êtes pas à l’origine de cette demande, changez vos accès et contactez-nous.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'data_export_ready',
            'url' => $this->url($notifiable),
            'expires_at' => $this->expiresAt->toIso8601String(),
        ];
    }
}
