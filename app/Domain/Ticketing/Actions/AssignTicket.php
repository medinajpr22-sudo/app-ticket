<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Events\TicketTaken;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Support\Facades\DB;

class AssignTicket
{
    public function __construct(private ChangeTicketStatus $changeStatus) {}

    /**
     * Hand the ticket to another agent of its team. When nobody had it yet,
     * it is as if that agent took it: the requester gets their greeting.
     */
    public function handle(Ticket $ticket, User $assignee, User $actor, ?string $reason = null): Ticket
    {
        if ($ticket->assignee()->is($assignee)) {
            return $ticket;
        }

        return DB::transaction(function () use ($ticket, $assignee, $actor, $reason): Ticket {
            $wasUnattended = $ticket->status === TicketStatus::Received;

            $ticket->assignee()->associate($assignee)->save();

            if ($wasUnattended) {
                $this->changeStatus->handle($ticket, TicketStatus::InProgress, $actor);
            }

            $ticket->assignments()->create([
                'team_id' => $ticket->team_id,
                'assignee_id' => $assignee->id,
                'user_id' => $actor->id,
            ]);

            $ticket->messages()->create([
                'author_id' => $actor->id,
                'type' => TicketMessageType::InternalNote,
                'body' => $this->noteFor($assignee, $reason),
            ]);

            if ($wasUnattended) {
                TicketTaken::dispatch($ticket, $assignee);
            }

            return $ticket;
        });
    }

    private function noteFor(User $assignee, ?string $reason): string
    {
        $note = __('Assigned to :name.', ['name' => $assignee->name]);

        return filled($reason) ? $note.' '.__('Reason: :reason', ['reason' => $reason]) : $note;
    }
}
