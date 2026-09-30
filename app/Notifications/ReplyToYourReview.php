<?php

namespace App\Notifications;

use App\Models\ReviewReply;
use App\Notifications\Concerns\DatabaseAndMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReplyToYourReview extends Notification implements ShouldQueue
{
    use DatabaseAndMail, Queueable;

    public function __construct(
        public ReviewReply $reply,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $business = $this->reply->loadMissing('review.business')->review->business;

        return (new MailMessage)
            ->subject(__(':business a répondu à votre avis', ['business' => $business->name]))
            ->line(__(':business a répondu à votre avis.', ['business' => $business->name]))
            ->action(__('Lire la réponse'), $this->url());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_reply',
            'reply_id' => $this->reply->id,
            'review_id' => $this->reply->review_id,
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        $business = $this->reply->loadMissing('review.business.city')->review->business;

        return route('city.business', ['city' => $business->city, 'business' => $business]).'#avis';
    }
}
