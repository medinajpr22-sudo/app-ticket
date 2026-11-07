<?php

namespace App\Domain\Ticketing\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

enum TicketChannel: string
{
    use HasTranslatedLabel;

    case Portal = 'portal';
    case Email = 'email';
    case Phone = 'phone';
}
