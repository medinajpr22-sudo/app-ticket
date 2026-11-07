<?php

namespace App\Http\Controllers\Reports;

use App\Domain\Catalog\Models\Category;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Department;
use App\Domain\Organization\Models\Team;
use App\Domain\Reporting\Services\TicketStatistics;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ShowOverviewRequest;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    /**
     * How many categories the chart shows; the rest are rarely asked for.
     */
    private const TOP_CATEGORIES = 10;

    /**
     * The analytical view of the whole help desk, for those who oversee it
     * instead of attending tickets: volume by area, category and team, SLA,
     * speed and ratings, and the performance of every team and agent.
     */
    public function __invoke(ShowOverviewRequest $request, TicketStatistics $statistics): Response
    {
        $filters = $request->filters();
        $tickets = $statistics->tickets($filters);

        $teams = Team::query()->orderBy('name')->get(['id', 'name']);
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        $categoryNames = Category::query()->pluck('name', 'id')->all();
        $openNow = $statistics->openTicketsByAssignee(null);

        $ticketsByTeam = $tickets->groupBy('team_id');
        $ticketsByAgent = $tickets->whereNotNull('assignee_id')->groupBy('assignee_id');
        $agents = User::query()->whereKey($ticketsByAgent->keys())->with('teams')->orderBy('name')->get();

        return Inertia::render('Reports/Overview', [
            'filters' => [
                ...$filters->period->toArray(),
                'department' => (string) ($filters->departmentId ?? ''),
                'team' => (string) ($filters->teamId ?? ''),
                'status' => $request->validated('status') ?? '',
            ],
            'summary' => $statistics->summarize($tickets),
            'byDepartment' => $statistics->countBy($tickets, 'requester_department_id', $departments->pluck('name', 'id')->all(), __('No area')),
            'byCategory' => array_slice($statistics->countBy($tickets, 'category_id', $categoryNames, __('No category')), 0, self::TOP_CATEGORIES),
            'byTeam' => $statistics->countBy($tickets, 'team_id', $teams->pluck('name', 'id')->all(), __('No team')),
            'teamPerformance' => $teams
                ->filter(fn (Team $team): bool => $ticketsByTeam->has($team->id))
                ->map(fn (Team $team): array => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'metrics' => $statistics->summarize($ticketsByTeam->get($team->id)),
                ])
                ->values()
                ->all(),
            'agentPerformance' => $agents
                ->map(fn (User $agent): array => [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'initials' => $agent->initials(),
                    'teams' => $agent->teams->pluck('name')->sort()->values()->all(),
                    'open_now' => $openNow[$agent->id] ?? 0,
                    'metrics' => $statistics->summarize($ticketsByAgent->get($agent->id, new Collection)),
                ])
                ->sortByDesc(fn (array $row): int => $row['metrics']['resolved'])
                ->values()
                ->all(),
            'options' => [
                'departments' => $departments->map(fn (Department $department): array => ['id' => $department->id, 'name' => $department->name])->all(),
                'teams' => $teams->map(fn (Team $team): array => ['id' => $team->id, 'name' => $team->name])->all(),
                'statuses' => TicketStatus::options(TicketStatus::current()),
            ],
            'platform' => [
                'open_tickets' => Ticket::query()->open()->count(),
                'active_users' => User::query()->active()->count(),
                'staff' => User::query()->staff()->active()->count(),
                'teams' => Team::query()->active()->count(),
            ],
        ]);
    }
}
