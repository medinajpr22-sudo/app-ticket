<?php

namespace App\Domain\Ticketing\Exceptions;

use App\Domain\Ticketing\Models\Ticket;
use DomainException;

/**
 * A closed ticket is final. When the problem comes back, the requester
 * opens a new ticket; the closed one is never reopened.
 */
class TicketIsClosed extends DomainException
{
    public static function cannotChangeStatus(Ticket $ticket): self
    {
        return new self("Ticket #{$ticket->id} is closed and its status can no longer change.");
    }
}
