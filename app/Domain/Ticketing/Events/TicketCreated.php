<?php

namespace App\Domain\Ticketing\Events;

use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class TicketCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public Ticket $ticket) {}
}
