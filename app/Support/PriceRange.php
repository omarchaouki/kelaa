<?php

namespace App\Support;

final class PriceRange
{
    public static function label(?int $level): ?string
    {
        return match ($level) {
            1 => __('Petit prix'),
            2 => __('Prix moyen'),
            3 => __('Prix élevé'),
            4 => __('Haut de gamme'),
            default => null,
        };
    }
}
