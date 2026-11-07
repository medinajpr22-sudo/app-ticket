<?php

namespace App\Domain\Ticketing\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

/**
 * Where a ticket stands against its SLA right now.
 */
enum SlaState: string
{
    use HasTranslatedLabel;

    case OnTrack = 'on_track';
    case AtRisk = 'at_risk';
    case Breached = 'breached';
    case Paused = 'paused';
    case Met = 'met';
    case Missed = 'missed';
}
