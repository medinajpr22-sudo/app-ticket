<?php

namespace App\Domain\Ticketing\Events;

use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Someone wrote in the conversation of a ticket.
 */
class TicketMessagePosted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public TicketMessage $message) {}
}
