<?php

namespace App\Domain\Ticketing\Events;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class TicketTaken implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public Ticket $ticket, public User $agent) {}
}
