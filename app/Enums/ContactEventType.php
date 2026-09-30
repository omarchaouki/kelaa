<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ContactEventType: string implements HasLabel
{
    use HasTranslatedLabel;

    case ProfileView = 'profile_view';
    case CallClick = 'call_click';
    case WhatsappClick = 'whatsapp_click';
    case DirectionsClick = 'directions_click';
    case WebsiteClick = 'website_click';
}
