<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Events\TicketTaken;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TakeTicket
{
    /**
     * Assign a new ticket to the agent who takes it.
     *
     * The assignment is a single conditional update, so when two agents press
     * "Take" at the same time only one of them gets the ticket.
     *
     * @throws ValidationException when someone else already took it.
     */
    public function handle(Ticket $ticket, User $agent): Ticket
    {
        return DB::transaction(function () use ($ticket, $agent): Ticket {
            $wasTaken = Ticket::query()
                ->whereKey($ticket->id)
                ->whereNull('assignee_id')
                ->where('status', TicketStatus::New)
                ->update([
                    'assignee_id' => $agent->id,
                    'status' => TicketStatus::InProgress,
                    'updated_at' => now(),
                ]) === 1;

            $ticket->refresh();

            if (! $wasTaken) {
                throw ValidationException::withMessages([
                    'ticket' => $ticket->assignee
                        ? __(':name already took this ticket.', ['name' => $ticket->assignee->name])
                        : __('This ticket can no longer be taken.'),
                ]);
            }

            $ticket->statusChanges()->create([
                'user_id' => $agent->id,
                'from_status' => TicketStatus::New,
                'to_status' => TicketStatus::InProgress,
            ]);

            $ticket->assignments()->create([
                'team_id' => $ticket->team_id,
                'assignee_id' => $agent->id,
                'user_id' => $agent->id,
            ]);

            TicketTaken::dispatch($ticket, $agent);

            return $ticket;
        });
    }
}
