<?php

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

/**
 * Labels live in lang/{locale}/enums.php under the snake-cased enum name.
 */
trait HasTranslatedLabel
{
    public function getLabel(): string
    {
        $group = Str::snake(class_basename(static::class));

        return __("enums.{$group}.{$this->value}");
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
