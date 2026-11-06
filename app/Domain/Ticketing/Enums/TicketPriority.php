<?php

namespace App\Domain\Ticketing\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

/**
 * Ordered from the most to the least urgent, which is the order used on screen.
 */
enum TicketPriority: string
{
    use HasTranslatedLabel;

    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
}
