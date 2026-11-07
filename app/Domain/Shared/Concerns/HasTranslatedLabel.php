<?php

namespace App\Domain\Shared\Concerns;

use Illuminate\Support\Str;

/**
 * Gives a backed enum a translated label and a list of options for select inputs.
 *
 * Labels live in lang/{locale}/enums.php, grouped by the enum's snake_case
 * class name and keyed by the case name.
 */
trait HasTranslatedLabel
{
    public function label(): string
    {
        return __('enums.'.Str::snake(class_basename(static::class)).'.'.$this->name);
    }

    /**
     * @param  list<self>|null  $cases  Only these cases; every case when null.
     * @return list<array{value: string, label: string}>
     */
    public static function options(?array $cases = null): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            $cases ?? self::cases(),
        );
    }
}
