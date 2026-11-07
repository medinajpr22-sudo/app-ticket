<?php

namespace App\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Actions\MarkTicketNotificationsAsRead;
use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\MessageTemplate;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketAttachment;
use App\Domain\Ticketing\Services\MessageTemplateRenderer;
use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    /**
     * The team inbox: only the tickets of the person's teams, the ones that
     * run out of time first at the top.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        /** @var array{view?: string|null, status?: string|null, team?: int|string|null, search?: string|null} $filters */
        $filters = $request->validate([
            'view' => ['nullable', Rule::in(['mine', 'unassigned'])],
            'status' => ['nullable', Rule::in(['open', 'all', ...array_map(fn (TicketStatus $status): string => $status->value, TicketStatus::current())])],
            'team' => ['nullable', 'integer', Rule::in($user->teams->modelKeys())],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $status = $filters['status'] ?? 'open';

        $tickets = $this->visibleTo($user)
            ->when($filters['view'] ?? null, fn (Builder $query, string $view) => $view === 'mine'
                ? $query->whereBelongsTo($user, 'assignee')
                : $query->whereNull('assignee_id'))
            ->when($status === 'open', fn (Builder $query) => $query->open())
            ->when(! in_array($status, ['open', 'all'], true), fn (Builder $query) => $query->where('status', $status))
            ->when($filters['team'] ?? null, fn (Builder $query, int|string $team) => $query->where('team_id', $team))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->whereLike('subject', "%{$search}%")
                    ->when(ctype_digit(ltrim($search, '#')), fn (Builder $query) => $query->orWhere('id', (int) ltrim($search, '#'))),
            ))
            ->with(['requester', 'assignee', 'team', 'category', 'businessCalendar.hours', 'businessCalendar.holidays'])
            ->orderByUrgency()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Desk/Tickets/Index', [
            'tickets' => TicketResource::collection($tickets),
            'filters' => [
                'view' => $filters['view'] ?? '',
                'status' => $status,
                'team' => (string) ($filters['team'] ?? ''),
                'search' => $filters['search'] ?? '',
            ],
            'counts' => [
                'mine' => $this->visibleTo($user)->open()->whereBelongsTo($user, 'assignee')->count(),
                'unassigned' => $this->visibleTo($user)->open()->whereNull('assignee_id')->count(),
            ],
            'teams' => $user->teams()->orderBy('name')->get()->map(fn (Team $team): array => ['id' => $team->id, 'name' => $team->name])->all(),
            'statuses' => TicketStatus::options(TicketStatus::current()),
        ]);
    }

    /**
     * A ticket as IT sees it: the whole conversation, internal notes included.
     */
    public function show(
        Request $request,
        Ticket $ticket,
        MessageTemplateRenderer $renderer,
        MarkTicketNotificationsAsRead $markNotificationsAsRead,
    ): Response {
        Gate::authorize('viewInDesk', $ticket);

        $markNotificationsAsRead->handle($request->user(), $ticket);

        $ticket->load([
            'requester.department',
            'requester.location',
            'assignee',
            'team',
            'category',
            'businessCalendar.hours',
            'businessCalendar.holidays',
            'rating',
            'closedBy',
            'messages' => fn ($query) => $query->with(['author', 'attachments'])->oldest()->orderBy('id'),
        ]);

        $teamMembers = $ticket->team->members()->where('is_active', true)->orderBy('name')->get();
        $otherTeams = Team::query()->active()->whereKeyNot($ticket->team_id)->orderBy('name')->get();

        $cannedResponses = MessageTemplate::query()
            ->where('purpose', MessageTemplatePurpose::CannedResponse)
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('team_id')->orWhere('team_id', $ticket->team_id))
            ->orderBy('name')
            ->get();

        return Inertia::render('Desk/Tickets/Show', [
            'ticket' => TicketResource::make($ticket),
            'cannedResponses' => $cannedResponses->map(fn (MessageTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'body' => $renderer->render($template->body, $ticket, $request->user()),
            ])->all(),
            'statusOptions' => array_map(fn (TicketStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ], TicketStatus::agentChoices()),
            'priorityOptions' => TicketPriority::options(),
            'teamMembers' => $teamMembers->map(fn (User $member): array => ['id' => $member->id, 'name' => $member->name])->all(),
            'otherTeams' => $otherTeams->map(fn (Team $team): array => ['id' => $team->id, 'name' => $team->name])->all(),
            'attachmentLimits' => TicketAttachment::limits(),
            'can' => [
                'take' => Gate::allows('take', $ticket),
                'respond' => Gate::allows('respond', $ticket),
                'changeStatus' => Gate::allows('changeStatus', $ticket),
                'changePriority' => Gate::allows('changePriority', $ticket),
                'assign' => Gate::allows('assign', $ticket),
                'transfer' => Gate::allows('transfer', $ticket),
            ],
        ]);
    }

    /**
     * The inbox is where people work, so it holds the tickets of their own
     * teams only. Seeing every ticket is what the statistics are for.
     *
     * @return Builder<Ticket>
     */
    private function visibleTo(User $user): Builder
    {
        return Ticket::query()->ofTeamsOf($user);
    }
}
