<?php

namespace App\Notifications\Concerns;

use App\Models\User;

/**
 * In-app always; e-mail only when the account has one. The language is the
 * recipient's own (User implements HasLocalePreference).
 */
trait DatabaseAndMail
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->email !== null) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
