<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Data\TicketFilters;
use App\Domain\ServiceLevel\Models\BusinessCalendar;
use App\Domain\ServiceLevel\Services\BusinessTimeCalculator;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketRating;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Numbers about how tickets are attended: volume, SLA, speed and ratings.
 *
 * Tickets are counted by the day they were created. Times are measured in
 * business minutes with each ticket's own calendar, like the SLA itself, so
 * nights and holidays do not make anyone look slow.
 *
 * Rows are aggregated in PHP so the same code runs on SQLite and PostgreSQL.
 * That is fine for the volume of an internal help desk; if it ever grows
 * past hundreds of thousands of tickets per period, precompute daily totals.
 */
class TicketStatistics
{
    /**
     * @var array<int, BusinessCalendar>
     */
    private array $calendars = [];

    public function __construct(private BusinessTimeCalculator $calculator) {}

    /**
     * The tickets the filters select, with only the columns the metrics use.
     *
     * @return Collection<int, Ticket>
     */
    public function tickets(TicketFilters $filters): Collection
    {
        $tickets = Ticket::query()
            ->select([
                'id',
                'team_id',
                'category_id',
                'assignee_id',
                'business_calendar_id',
                'status',
                'created_at',
                'first_responded_at',
                'first_response_due_at',
                'resolution_due_at',
                'resolved_at',
            ])
            ->addSelect([
                'requester_department_id' => User::query()->select('department_id')->whereColumn('users.id', 'tickets.requester_id'),
                'rating_score' => TicketRating::query()->select('score')->whereColumn('ticket_ratings.ticket_id', 'tickets.id'),
            ])
            ->whereBetween('created_at', [$filters->period->from, $filters->period->to])
            ->when($filters->teamIds !== null, fn (Builder $query) => $query->whereIn('team_id', $filters->teamIds))
            ->when($filters->teamId, fn (Builder $query, int $teamId) => $query->where('team_id', $teamId))
            ->when($filters->departmentId, fn (Builder $query, int $departmentId) => $query->whereRelation('requester', 'department_id', $departmentId))
            ->when($filters->status === 'open', fn (Builder $query) => $query->open())
            ->when($filters->status instanceof TicketStatus, fn (Builder $query) => $query->where('status', $filters->status))
            ->get();

        $this->loadCalendars($tickets);

        return $tickets->toBase();
    }

    /**
     * The headline numbers of a group of tickets.
     *
     * @param  Collection<int, Ticket>  $tickets
     * @return array{
     *     total: int,
     *     received: int,
     *     in_progress: int,
     *     waiting: int,
     *     open: int,
     *     resolved: int,
     *     resolution_compliance: float|null,
     *     first_response_compliance: float|null,
     *     average_first_response_minutes: int|null,
     *     average_resolution_minutes: int|null,
     *     average_rating: float|null,
     *     ratings: int,
     * }
     */
    public function summarize(Collection $tickets): array
    {
        $responded = $tickets->filter(fn (Ticket $ticket): bool => $ticket->first_responded_at !== null);
        $resolved = $tickets->filter(fn (Ticket $ticket): bool => $ticket->resolved_at !== null);
        $ratings = $tickets->pluck('rating_score')->filter(fn (mixed $score): bool => $score !== null);

        return [
            'total' => $tickets->count(),
            'received' => $tickets->where('status', TicketStatus::Received)->count(),
            'in_progress' => $tickets->where('status', TicketStatus::InProgress)->count(),
            'waiting' => $tickets->where('status', TicketStatus::WaitingOnRequester)->count(),
            'open' => $tickets->filter(fn (Ticket $ticket): bool => $ticket->status->isOpen())->count(),
            'resolved' => $resolved->count(),
            'resolution_compliance' => $this->share($resolved, fn (Ticket $ticket): bool => $ticket->resolved_at <= $ticket->resolution_due_at),
            'first_response_compliance' => $this->share($responded, fn (Ticket $ticket): bool => $ticket->first_responded_at <= $ticket->first_response_due_at),
            'average_first_response_minutes' => $this->averageBusinessMinutes($responded, fn (Ticket $ticket): CarbonInterface => $ticket->first_responded_at),
            'average_resolution_minutes' => $this->averageBusinessMinutes($resolved, fn (Ticket $ticket): CarbonInterface => $ticket->resolved_at),
            'average_rating' => $ratings->isEmpty() ? null : round((float) $ratings->avg(), 1),
            'ratings' => $ratings->count(),
        ];
    }

    /**
     * How many tickets fall in each group, largest first; ties by name.
     *
     * @param  Collection<int, Ticket>  $tickets
     * @param  array<int, string>  $labels  Group names by id.
     * @return list<array{id: int|null, label: string, total: int}>
     */
    public function countBy(Collection $tickets, string $attribute, array $labels, string $missingLabel): array
    {
        return $tickets
            ->countBy(fn (Ticket $ticket): int => (int) $ticket->getAttribute($attribute))
            ->map(fn (int $total, int $id): array => [
                'id' => $id === 0 ? null : $id,
                'label' => $labels[$id] ?? $missingLabel,
                'total' => $total,
            ])
            ->sortBy([['total', 'desc'], ['label', 'asc']])
            ->values()
            ->all();
    }

    /**
     * Open tickets each agent has right now, whatever the period. It is the
     * workload a lead balances, not a result.
     *
     * @param  list<int>|null  $teamIds  null for every team.
     * @return array<int, int>
     */
    public function openTicketsByAssignee(?array $teamIds): array
    {
        return Ticket::query()
            ->open()
            ->whereNotNull('assignee_id')
            ->when($teamIds !== null, fn (Builder $query) => $query->whereIn('team_id', $teamIds))
            ->selectRaw('assignee_id, count(*) as total')
            ->groupBy('assignee_id')
            ->pluck('total', 'assignee_id')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    /**
     * Percentage of the tickets that pass the check, or null when there are none.
     *
     * @param  Collection<int, Ticket>  $tickets
     * @param  Closure(Ticket): bool  $passes
     */
    private function share(Collection $tickets, Closure $passes): ?float
    {
        if ($tickets->isEmpty()) {
            return null;
        }

        return round($tickets->filter($passes)->count() * 100 / $tickets->count(), 1);
    }

    /**
     * Average working time from the creation of each ticket to a moment of it.
     *
     * @param  Collection<int, Ticket>  $tickets
     * @param  Closure(Ticket): CarbonInterface  $until
     */
    private function averageBusinessMinutes(Collection $tickets, Closure $until): ?int
    {
        if ($tickets->isEmpty()) {
            return null;
        }

        return (int) round($tickets->avg(fn (Ticket $ticket): int => $this->calculator->minutesBetween(
            $this->calendars[$ticket->business_calendar_id],
            $ticket->created_at,
            $until($ticket),
        )));
    }

    /**
     * @param  EloquentCollection<int, Ticket>  $tickets
     */
    private function loadCalendars(EloquentCollection $tickets): void
    {
        $missing = $tickets->pluck('business_calendar_id')->unique()->diff(array_keys($this->calendars));

        if ($missing->isEmpty()) {
            return;
        }

        BusinessCalendar::query()
            ->with(['hours', 'holidays'])
            ->findMany($missing->all())
            ->each(function (BusinessCalendar $calendar): void {
                $this->calendars[$calendar->id] = $calendar;
            });
    }
}
