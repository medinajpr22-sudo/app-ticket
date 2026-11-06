<?php

namespace App\Domain\Ticketing\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

enum TicketType: string
{
    use HasTranslatedLabel;

    case Incident = 'incident';
    case ServiceRequest = 'service_request';

    public function description(): string
    {
        return __('enums.ticket_type_description.'.$this->name);
    }
}
