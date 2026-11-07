<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Events\TicketTaken;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TakeTicket
{
    public function __construct(private ChangeTicketStatus $changeStatus) {}

    /**
     * Make the agent responsible for the ticket, so they can write in it.
     *
     * The agent confirms on the screen what they saw: nobody had the ticket,
     * or a given colleague had it. The assignment is a single conditional
     * update on that same state, so when two agents take the ticket at once
     * only one gets it, and nobody takes it from a person they did not see.
     *
     * A ticket nobody had starts being worked on and the requester gets the
     * team's greeting. Taking it from a colleague only changes who has it.
     *
     * @param  int|null  $expectedAssigneeId  Who had the ticket when the agent decided.
     *
     * @throws ValidationException when the ticket changed hands in the meantime.
     */
    public function handle(Ticket $ticket, User $agent, ?int $expectedAssigneeId = null): Ticket
    {
        if ($ticket->assignee()->is($agent)) {
            return $ticket;
        }

        return DB::transaction(function () use ($ticket, $agent, $expectedAssigneeId): Ticket {
            $wasTaken = Ticket::query()
                ->whereKey($ticket->id)
                ->where('status', '!=', TicketStatus::Closed)
                ->when(
                    $expectedAssigneeId === null,
                    fn ($query) => $query->whereNull('assignee_id'),
                    fn ($query) => $query->where('assignee_id', $expectedAssigneeId),
                )
                ->update(['assignee_id' => $agent->id, 'updated_at' => now()]) === 1;

            $ticket->refresh();

            if (! $wasTaken) {
                throw ValidationException::withMessages([
                    'ticket' => $ticket->assignee && $ticket->status !== TicketStatus::Closed
                        ? __(':name already took this ticket.', ['name' => $ticket->assignee->name])
                        : __('This ticket can no longer be taken.'),
                ]);
            }

            if ($ticket->status === TicketStatus::Received) {
                $this->changeStatus->handle($ticket, TicketStatus::InProgress, $agent);
            }

            $ticket->assignments()->create([
                'team_id' => $ticket->team_id,
                'assignee_id' => $agent->id,
                'user_id' => $agent->id,
            ]);

            if ($expectedAssigneeId === null) {
                TicketTaken::dispatch($ticket, $agent);

                return $ticket;
            }

            $ticket->messages()->create([
                'author_id' => $agent->id,
                'type' => TicketMessageType::InternalNote,
                'body' => __(':agent took the ticket from :previous.', [
                    'agent' => $agent->name,
                    'previous' => User::query()->find($expectedAssigneeId)?->name ?? __('another agent'),
                ]),
            ]);

            return $ticket;
        });
    }
}
