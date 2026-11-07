<?php

namespace App\Domain\Identity\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

/**
 * How permissions are grouped on the roles screen.
 */
enum PermissionGroup: string
{
    use HasTranslatedLabel;

    case Tickets = 'tickets';
    case Reports = 'reports';
    case Leadership = 'leadership';
    case Management = 'management';
}
