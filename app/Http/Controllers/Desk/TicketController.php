<?php

namespace App\Http\Controllers\Desk;

use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Enums\MessageTemplatePurpose;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\MessageTemplate;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Services\MessageTemplateRenderer;
use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    /**
     * The team inbox, most urgent first.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        /** @var array{view?: string|null, status?: string|null, team?: int|string|null, search?: string|null} $filters */
        $filters = $request->validate([
            'view' => ['nullable', Rule::in(['mine', 'unassigned'])],
            'status' => ['nullable', Rule::in(['all', ...array_map(fn (TicketStatus $status): string => $status->value, TicketStatus::cases())])],
            'team' => ['nullable', 'integer', Rule::exists(Team::class, 'id')],
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
            ->orderBy('resolution_due_at')
            ->orderBy('id')
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
            'teams' => $this->teamsFor($user)->map(fn (Team $team): array => ['id' => $team->id, 'name' => $team->name])->all(),
            'statuses' => TicketStatus::options(),
        ]);
    }

    /**
     * A ticket as IT sees it: the whole conversation, internal notes included.
     */
    public function show(Request $request, Ticket $ticket, MessageTemplateRenderer $renderer): Response
    {
        Gate::authorize('viewInDesk', $ticket);

        $ticket->load([
            'requester.department',
            'requester.location',
            'assignee',
            'team',
            'category',
            'businessCalendar.hours',
            'businessCalendar.holidays',
            'messages' => fn ($query) => $query->with('author')->oldest()->orderBy('id'),
        ]);

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
            'can' => [
                'take' => Gate::allows('take', $ticket),
                'respond' => Gate::allows('respond', $ticket),
                'addInternalNote' => Gate::allows('addInternalNote', $ticket),
                'changeStatus' => Gate::allows('changeStatus', $ticket),
            ],
        ]);
    }

    /**
     * Tickets of the person's teams, or every ticket for those who may see them all.
     *
     * @return Builder<Ticket>
     */
    private function visibleTo(User $user): Builder
    {
        return Ticket::query()->when(
            ! $user->can(PermissionSlug::ViewAllTickets->value),
            fn (Builder $query) => $query->ofTeamsOf($user),
        );
    }

    /**
     * Teams the person can filter the inbox by.
     *
     * @return Collection<int, Team>
     */
    private function teamsFor(User $user): Collection
    {
        return $user->can(PermissionSlug::ViewAllTickets->value)
            ? Team::query()->orderBy('name')->get()
            : $user->teams()->orderBy('name')->get();
    }
}
