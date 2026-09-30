<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    use HasTranslatedLabel;

    case BankTransfer = 'bank_transfer';
    case CashAgency = 'cash_agency';
    case Cmi = 'cmi';
    case Manual = 'manual';
}
