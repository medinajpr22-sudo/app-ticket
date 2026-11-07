<?php

namespace App\Domain\Ticketing\Events;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class TicketStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  User|null  $actor  Who made the change; null when the system did.
     */
    public function __construct(
        public Ticket $ticket,
        public TicketStatus $from,
        public TicketStatus $to,
        public ?User $actor,
    ) {}
}
