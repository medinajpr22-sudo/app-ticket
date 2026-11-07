<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Models\SlaTarget;
use App\Domain\ServiceLevel\Services\BusinessTimeCalculator;
use App\Domain\Ticketing\Enums\TicketMessageType;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketStatusChange;
use Illuminate\Support\Facades\DB;
use LogicException;

class ChangeTicketPriority
{
    public function __construct(private BusinessTimeCalculator $calculator) {}

    /**
     * Reclassify the ticket and recalculate its deadlines with the targets of
     * the new priority, counted from creation plus the time it was paused.
     */
    public function handle(Ticket $ticket, TicketPriority $priority, User $actor, ?string $reason = null): Ticket
    {
        if ($ticket->priority === $priority) {
            return $ticket;
        }

        return DB::transaction(function () use ($ticket, $priority, $actor, $reason): Ticket {
            $policy = $ticket->category->effectiveSlaPolicy()->load('targets');
            $target = $policy->targets->first(fn (SlaTarget $target): bool => $target->priority === $priority)
                ?? throw new LogicException("The SLA policy [{$policy->name}] has no target for [{$priority->value}].");
            $calendar = $ticket->businessCalendar()->with(['hours', 'holidays'])->firstOrFail();
            $pausedMinutes = $this->finishedPausesInMinutes($ticket, $calendar);
            $previous = $ticket->priority;

            $ticket->priority = $priority;
            $ticket->first_response_due_at = $this->calculator->addMinutes($calendar, $ticket->created_at, $target->first_response_minutes + $pausedMinutes);
            $ticket->resolution_due_at = $this->calculator->addMinutes($calendar, $ticket->created_at, $target->resolution_minutes + $pausedMinutes);
            $ticket->save();

            $note = __('Priority changed from :from to :to.', ['from' => $previous->label(), 'to' => $priority->label()]);

            $ticket->messages()->create([
                'author_id' => $actor->id,
                'type' => TicketMessageType::InternalNote,
                'body' => filled($reason) ? $note.' '.__('Reason: :reason', ['reason' => $reason]) : $note,
            ]);

            return $ticket;
        });
    }

    /**
     * Working minutes spent in statuses that pause the SLA, read from the status
     * history. A pause still in progress is left out: resuming adds it later.
     */
    private function finishedPausesInMinutes(Ticket $ticket, BusinessCalendar $calendar): int
    {
        $minutes = 0;
        $pausedSince = null;

        foreach ($ticket->statusChanges()->oldest()->orderBy('id')->get() as $change) {
            /** @var TicketStatusChange $change */
            if ($pausedSince !== null && $change->from_status?->pausesSla() && ! $change->to_status->pausesSla()) {
                $minutes += $this->calculator->minutesBetween($calendar, $pausedSince, $change->created_at);
                $pausedSince = null;
            }

            if ($change->to_status->pausesSla() && ! $change->from_status?->pausesSla()) {
                $pausedSince = $change->created_at;
            }
        }

        return $minutes;
    }
}
