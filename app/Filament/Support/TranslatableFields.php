<?php

namespace App\Filament\Support;

use App\Support\Locales;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Illuminate\Database\Eloquent\Model;

/**
 * Side-by-side inputs for a spatie/laravel-translatable attribute
 * (name.fr, name.ar, name.en), so admins see every language at once.
 */
class TranslatableFields
{
    /**
     * @param  (Closure(string): Field)|null  $field  Builds the input for one locale path, e.g. "name.fr".
     */
    public static function make(string $attribute, string $label, ?Closure $field = null, bool $required = true): Fieldset
    {
        $field ??= fn (string $path): Field => TextInput::make($path)->maxLength(255);

        $inputs = [];

        foreach (Locales::supported() as $locale) {
            $input = $field("{$attribute}.{$locale}")
                ->label(Locales::nativeName($locale))
                ->formatStateUsing(fn (?Model $record) => $record?->getTranslation($attribute, $locale, false))
                ->extraInputAttributes(['dir' => Locales::direction($locale), 'lang' => $locale]);

            // The default locale is mandatory; the others fall back to it.
            if ($required && $locale === Locales::default()) {
                $input->required();
            }

            $inputs[] = $input;
        }

        return Fieldset::make($label)->schema($inputs)->columns(count($inputs));
    }
}
