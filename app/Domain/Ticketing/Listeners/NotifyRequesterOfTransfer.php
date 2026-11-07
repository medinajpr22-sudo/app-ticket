<?php

namespace App\Domain\Ticketing\Listeners;

use App\Domain\Ticketing\Enums\TicketActivity;
use App\Domain\Ticketing\Events\TicketTransferred;
use App\Domain\Ticketing\Notifications\TicketActivityNotification;

class NotifyRequesterOfTransfer
{
    /**
     * The requester learns which team continues with their ticket.
     */
    public function handle(TicketTransferred $event): void
    {
        $event->ticket->requester->notify(
            new TicketActivityNotification($event->ticket, TicketActivity::Transferred, $event->actor->name),
        );
    }
}
