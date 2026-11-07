<?php

namespace App\Domain\Ticketing\Listeners;

use App\Domain\Ticketing\Enums\TicketActivity;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Events\TicketMessagePosted;
use App\Domain\Ticketing\Notifications\TicketActivityNotification;

class NotifyAboutNewMessage
{
    /**
     * An answer from IT reaches the requester. An answer from the requester
     * reaches the agent who has the ticket. Nobody is told about their own message.
     */
    public function handle(TicketMessagePosted $event): void
    {
        $message = $event->message;
        $author = $message->author;

        if ($message->type !== TicketMessageType::Reply || $author === null) {
            return;
        }

        $ticket = $message->ticket;

        if ($ticket->requester_id !== $author->id) {
            $ticket->requester->notify(
                new TicketActivityNotification($ticket, TicketActivity::NewMessage, $author->name, $message->body),
            );

            return;
        }

        if ($ticket->assignee !== null && $ticket->assignee_id !== $author->id) {
            $ticket->assignee->notify(
                new TicketActivityNotification($ticket, TicketActivity::RequesterReplied, $author->name, $message->body),
            );
        }
    }
}
