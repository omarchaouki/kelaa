<?php

namespace App\Notifications;

use App\Models\Review;
use App\Notifications\Concerns\DatabaseAndMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReviewOnYourBusiness extends Notification implements ShouldQueue
{
    use DatabaseAndMail, Queueable;

    public function __construct(
        public Review $review,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $business = $this->review->loadMissing('business')->business;

        return (new MailMessage)
            ->subject(__('Nouvel avis sur :business', ['business' => $business->name]))
            ->line(__('Un client a laissé un avis de :rating/5 sur :business.', ['rating' => $this->review->rating, 'business' => $business->name]))
            ->action(__('Voir l’avis'), $this->url())
            ->line(__('Vous pouvez y répondre publiquement depuis votre espace professionnel.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_review',
            'review_id' => $this->review->id,
            'business_id' => $this->review->business_id,
            'rating' => $this->review->rating,
            'url' => $this->url(),
        ];
    }

    private function url(): string
    {
        $business = $this->review->loadMissing('business.city')->business;

        return route('city.business', ['city' => $business->city, 'business' => $business]).'#avis';
    }
}
