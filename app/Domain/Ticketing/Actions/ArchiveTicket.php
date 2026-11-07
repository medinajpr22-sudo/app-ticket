<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Support\Facades\DB;

class ArchiveTicket
{
    public function __construct(private ChangeTicketStatus $changeStatus) {}

    /**
     * Take the ticket off the requester's list. Nothing is deleted: IT keeps
     * it as it is and the requester can bring it back. A ticket still open is
     * closed first, because removing it means they no longer need help.
     */
    public function handle(Ticket $ticket, User $requester): Ticket
    {
        return DB::transaction(function () use ($ticket, $requester): Ticket {
            if ($ticket->status !== TicketStatus::Closed) {
                $this->changeStatus->handle($ticket, TicketStatus::Closed, $requester);
            }

            $ticket->forceFill(['requester_archived_at' => now()])->save();

            return $ticket;
        });
    }
}
