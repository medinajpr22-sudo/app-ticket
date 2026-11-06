<?php

namespace App\Domain\ServiceLevel\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

/**
 * ISO-8601 day numbers, as returned by Carbon's dayOfWeekIso.
 */
enum Weekday: int
{
    use HasTranslatedLabel;

    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;
}
