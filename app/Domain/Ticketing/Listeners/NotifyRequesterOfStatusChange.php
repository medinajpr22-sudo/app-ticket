<?php

namespace App\Domain\Ticketing\Listeners;

use App\Domain\Ticketing\Enums\TicketActivity;
use App\Domain\Ticketing\Events\TicketStatusChanged;
use App\Domain\Ticketing\Notifications\TicketActivityNotification;

class NotifyRequesterOfStatusChange
{
    /**
     * The requester hears when IT needs their answer, proposes a solution or
     * closes the ticket. Changes the requester made themselves are not news.
     */
    public function handle(TicketStatusChanged $event): void
    {
        $activity = TicketActivity::forStatus($event->to);
        $ticket = $event->ticket;

        if ($activity === null || $event->actor?->id === $ticket->requester_id) {
            return;
        }

        $ticket->requester->notify(new TicketActivityNotification($ticket, $activity, $event->actor?->name));
    }
}
