<?php

namespace App\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Reporting\Data\TicketFilters;
use App\Domain\Reporting\Services\TicketStatistics;
use App\Http\Controllers\Controller;
use App\Http\Requests\Desk\ShowTeamRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * "My team" opens the first team the person leads.
     */
    public function index(Request $request): RedirectResponse
    {
        $team = $request->user()->ledTeams()->first();

        abort_if($team === null, 403);

        return to_route('desk.teams.show', $team);
    }

    /**
     * The team as its lead sees it: its numbers for the period and how each
     * member performs, side by side.
     */
    public function show(ShowTeamRequest $request, Team $team, TicketStatistics $statistics): Response
    {
        $period = $request->period();
        $tickets = $statistics->tickets(new TicketFilters(period: $period, teamIds: [$team->id]));
        $ticketsByMember = $tickets->whereNotNull('assignee_id')->groupBy('assignee_id');
        $openNow = $statistics->openTicketsByAssignee([$team->id]);

        $members = $team->members()->orderByPivot('is_lead', 'desc')->orderBy('name')->get();

        return Inertia::render('Desk/Teams/Show', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'icon' => $team->icon->value],
            'ledTeams' => $request->user()->ledTeams()->map(fn (Team $ledTeam): array => ['id' => $ledTeam->id, 'name' => $ledTeam->name])->all(),
            'filters' => $period->toArray(),
            'summary' => $statistics->summarize($tickets),
            'unassigned' => $tickets->whereNull('assignee_id')->filter(fn ($ticket): bool => $ticket->status->isOpen())->count(),
            'members' => $members->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'initials' => $member->initials(),
                'email' => $member->email,
                'job_title' => $member->job_title,
                'is_lead' => (bool) $member->pivot->getAttribute('is_lead'),
                'is_active' => $member->is_active,
                'last_login_at' => $member->last_login_at?->toIso8601String(),
                'open_now' => $openNow[$member->id] ?? 0,
                'metrics' => $statistics->summarize($ticketsByMember->get($member->id, new Collection)),
            ])->all(),
        ]);
    }
}
