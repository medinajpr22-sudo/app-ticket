<?php

namespace App\Domain\Ticketing\Services;

use App\Domain\ServiceLevel\Services\BusinessTimeCalculator;
use App\Domain\Ticketing\Enums\SlaState;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Support\Carbon;

/**
 * Tells how a ticket is doing against the deadline that matters now:
 * the first response until someone answers, then the resolution.
 */
class TicketSlaTracker
{
    /**
     * A ticket is at risk once less than this share of its working time is left.
     */
    private const AT_RISK_SHARE = 0.25;

    public function __construct(private BusinessTimeCalculator $calculator) {}

    /**
     * @return array{state: SlaState, target: 'first_response'|'resolution', due_at: Carbon}
     */
    public function evaluate(Ticket $ticket): array
    {
        $isAwaitingFirstResponse = $ticket->first_responded_at === null;
        $target = $isAwaitingFirstResponse ? 'first_response' : 'resolution';
        $dueAt = $isAwaitingFirstResponse ? $ticket->first_response_due_at : $ticket->resolution_due_at;

        return [
            'state' => $this->stateOf($ticket, $dueAt),
            'target' => $target,
            'due_at' => $dueAt,
        ];
    }

    private function stateOf(Ticket $ticket, Carbon $dueAt): SlaState
    {
        if (! $ticket->status->isOpen()) {
            $finishedAt = $ticket->resolved_at ?? $ticket->closed_at ?? now();

            return $finishedAt <= $ticket->resolution_due_at ? SlaState::Met : SlaState::Missed;
        }

        if ($ticket->status->pausesSla()) {
            return SlaState::Paused;
        }

        if (now() > $dueAt) {
            return SlaState::Breached;
        }

        $calendar = $ticket->businessCalendar;
        $total = $this->calculator->minutesBetween($calendar, $ticket->created_at, $dueAt);
        $remaining = $this->calculator->minutesBetween($calendar, now(), $dueAt);

        return $total > 0 && $remaining <= $total * self::AT_RISK_SHARE ? SlaState::AtRisk : SlaState::OnTrack;
    }
}
