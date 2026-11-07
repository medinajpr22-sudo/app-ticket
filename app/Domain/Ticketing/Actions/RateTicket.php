<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketRating;

class RateTicket
{
    /**
     * Record how the requester rated the attention.
     *
     * @param  array{score: int, comment?: string|null}  $attributes
     */
    public function handle(Ticket $ticket, array $attributes): TicketRating
    {
        return $ticket->rating()->create($attributes);
    }
}
