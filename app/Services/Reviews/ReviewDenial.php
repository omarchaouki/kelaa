<?php

namespace App\Services\Reviews;

enum ReviewDenial: string
{
    case Guest = 'guest';
    case Banned = 'banned';
    case PhoneNotVerified = 'phone_not_verified';
    case OwnBusiness = 'own_business';
    case ConflictOfInterest = 'conflict_of_interest';
    case DailyLimit = 'daily_limit';
    case BusinessUnavailable = 'business_unavailable';

    public function message(): string
    {
        return match ($this) {
            self::Guest => __('Connectez-vous pour laisser un avis.'),
            self::Banned => __('Ce compte est suspendu.'),
            self::PhoneNotVerified => __('Un numéro de mobile vérifié est nécessaire pour publier un avis.'),
            self::OwnBusiness => __('Vous ne pouvez pas évaluer votre propre établissement.'),
            self::ConflictOfInterest => __('Vous ne pouvez pas évaluer un concurrent direct de votre établissement.'),
            self::DailyLimit => __('Vous avez atteint le nombre maximum d’avis pour aujourd’hui.'),
            self::BusinessUnavailable => __('Cet établissement ne peut pas recevoir d’avis.'),
        };
    }
}
