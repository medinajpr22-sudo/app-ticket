<?php

namespace App\Http\Controllers\Desk;

use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Desk\ShowTicketHistoryRequest;
use App\Http\Resources\TicketResource;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class TicketHistoryController extends Controller
{
    /**
     * When a ticket was finished: when IT resolved it or, if it was closed
     * without going through "resolved", when it was closed.
     */
    private const FINISHED_AT = 'coalesce(resolved_at, closed_at)';

    /**
     * The tickets the person's teams resolved or closed, the most recent first.
     */
    public function __invoke(ShowTicketHistoryRequest $request): Response
    {
        $user = $request->user();
        $filters = $request->validated();
        $period = $request->periodOrNull();

        $teamHistory = fn (): Builder => Ticket::query()->ofTeamsOf($user)->finished();

        $tickets = $teamHistory()
            ->when(($filters['view'] ?? null) === 'mine', fn (Builder $query) => $query->whereBelongsTo($user, 'assignee'))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['team'] ?? null, fn (Builder $query, int|string $team) => $query->where('team_id', $team))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->matching($search))
            ->when($period, fn (Builder $query) => $query
                ->whereRaw(self::FINISHED_AT.' >= ?', [$period->from->toDateTimeString()])
                ->whereRaw(self::FINISHED_AT.' <= ?', [$period->to->toDateTimeString()]))
            ->with(['requester', 'assignee', 'team', 'category', 'closedBy', 'rating', 'businessCalendar.hours', 'businessCalendar.holidays'])
            ->orderByRaw(self::FINISHED_AT.' desc')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Desk/Tickets/History', [
            'tickets' => TicketResource::collection($tickets),
            'filters' => [
                'view' => $filters['view'] ?? '',
                'status' => $filters['status'] ?? '',
                'team' => (string) ($filters['team'] ?? ''),
                'search' => $filters['search'] ?? '',
                'from' => $period?->toArray()['from'] ?? '',
                'to' => $period?->toArray()['to'] ?? '',
            ],
            'counts' => [
                'all' => $teamHistory()->count(),
                'mine' => $teamHistory()->whereBelongsTo($user, 'assignee')->count(),
            ],
            'teams' => $user->teams()->orderBy('name')->get()->map(fn (Team $team): array => ['id' => $team->id, 'name' => $team->name])->all(),
            'statuses' => TicketStatus::options([TicketStatus::Resolved, TicketStatus::Closed]),
        ]);
    }
}
