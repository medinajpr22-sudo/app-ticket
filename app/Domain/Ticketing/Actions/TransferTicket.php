<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TransferTicket
{
    public function __construct(private ChangeTicketStatus $changeStatus) {}

    /**
     * Send the ticket to another team. It goes back to "received" so someone of
     * the new team takes it, and the requester is told who continues with it.
     * The SLA keeps its deadlines: moving a ticket does not buy more time.
     */
    public function handle(Ticket $ticket, Team $team, User $actor, string $reason): Ticket
    {
        return DB::transaction(function () use ($ticket, $team, $actor, $reason): Ticket {
            $previousTeam = $ticket->team;

            $ticket->team()->associate($team);
            $ticket->assignee()->dissociate();
            $ticket->save();

            $this->changeStatus->handle($ticket, TicketStatus::New, $actor);

            $ticket->assignments()->create([
                'team_id' => $team->id,
                'assignee_id' => null,
                'user_id' => $actor->id,
            ]);

            $ticket->messages()->create([
                'author_id' => $actor->id,
                'type' => TicketMessageType::InternalNote,
                'body' => __('Transferred from :from to :to.', ['from' => $previousTeam->name, 'to' => $team->name])
                    .' '.__('Reason: :reason', ['reason' => $reason]),
            ]);

            $ticket->messages()->create([
                'author_id' => null,
                'type' => TicketMessageType::Automated,
                'body' => __('Your request was transferred to the :team team, who will continue with it.', ['team' => $team->name]),
            ]);

            return $ticket;
        });
    }
}
