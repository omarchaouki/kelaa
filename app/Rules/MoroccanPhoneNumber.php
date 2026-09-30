<?php

namespace App\Rules;

use App\Support\Phone\MoroccanPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MoroccanPhoneNumber implements ValidationRule
{
    public function __construct(
        private readonly bool $mobileOnly = true,
    ) {}

    public static function mobile(): self
    {
        return new self(mobileOnly: true);
    }

    public static function any(): self
    {
        return new self(mobileOnly: false);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || MoroccanPhone::normalize($value, $this->mobileOnly) === null) {
            $fail($this->mobileOnly
                ? __('Entrez un numéro de mobile marocain valide (06 ou 07).')
                : __('Entrez un numéro de téléphone marocain valide.'));
        }
    }
}
