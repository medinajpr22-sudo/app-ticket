<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\ServiceLevel\Services\BusinessTimeCalculator;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Support\Facades\DB;

class ChangeTicketStatus
{
    public function __construct(private BusinessTimeCalculator $calculator) {}

    /**
     * Move a ticket to another status and keep its SLA honest: the clock stops
     * while waiting on the requester and the deadlines move by the paused time.
     *
     * @param  User|null  $actor  Who made the change; null when the system did.
     */
    public function handle(Ticket $ticket, TicketStatus $status, ?User $actor): Ticket
    {
        if ($ticket->status === $status) {
            return $ticket;
        }

        return DB::transaction(function () use ($ticket, $status, $actor): Ticket {
            $previous = $ticket->status;

            if ($status->pausesSla() && ! $previous->pausesSla()) {
                $ticket->sla_paused_at = now();
            }

            if ($previous->pausesSla() && ! $status->pausesSla()) {
                $this->resumeSla($ticket);
            }

            $ticket->status = $status;

            if ($status === TicketStatus::Resolved) {
                $ticket->resolved_at = now();
            }

            if (in_array($status, [TicketStatus::Closed, TicketStatus::Cancelled], true)) {
                $ticket->closed_at = now();
            }

            if ($status->isOpen()) {
                $ticket->resolved_at = null;
                $ticket->closed_at = null;
            }

            $ticket->save();

            $ticket->statusChanges()->create([
                'user_id' => $actor?->id,
                'from_status' => $previous,
                'to_status' => $status,
            ]);

            return $ticket;
        });
    }

    /**
     * Push the pending deadlines forward by the working time the ticket spent paused.
     */
    private function resumeSla(Ticket $ticket): void
    {
        if ($ticket->sla_paused_at === null) {
            return;
        }

        $calendar = $ticket->businessCalendar()->with(['hours', 'holidays'])->firstOrFail();
        $pausedMinutes = $this->calculator->minutesBetween($calendar, $ticket->sla_paused_at, now());

        $ticket->resolution_due_at = $this->calculator->addMinutes($calendar, $ticket->resolution_due_at, $pausedMinutes);

        if ($ticket->first_responded_at === null) {
            $ticket->first_response_due_at = $this->calculator->addMinutes($calendar, $ticket->first_response_due_at, $pausedMinutes);
        }

        $ticket->sla_paused_at = null;
    }
}
