<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Catalog\Models\Category;
use App\Domain\ServiceLevel\Models\SlaPolicy;
use App\Domain\ServiceLevel\Models\SlaTarget;
use App\Domain\Ticketing\Actions\CreateTicket;
use App\Domain\Ticketing\Actions\MarkTicketNotificationsAsRead;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketAttachment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    /**
     * Display the tickets the person asked for.
     */
    public function index(Request $request): Response
    {
        /** @var array{status?: string|null} $filters */
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['open', 'finished', 'archived'])],
        ]);

        $status = $filters['status'] ?? null;

        $tickets = Ticket::query()
            ->whereBelongsTo($request->user(), 'requester')
            ->when(
                $status === 'archived',
                fn (Builder $query) => $query->whereNotNull('requester_archived_at'),
                fn (Builder $query) => $query->whereNull('requester_archived_at'),
            )
            ->when($status === 'open', fn (Builder $query) => $query->whereIn('status', TicketStatus::open()))
            ->when($status === 'finished', fn (Builder $query) => $query->whereNotIn('status', TicketStatus::open()))
            ->with(['team', 'category', 'assignee'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Portal/Tickets/Index', [
            'tickets' => TicketResource::collection($tickets),
            'filters' => ['status' => $status ?? ''],
        ]);
    }

    /**
     * Show the catalog to open a ticket.
     */
    public function create(): Response
    {
        $categories = Category::query()
            ->where('is_visible', true)
            ->whereHas('team', fn (Builder $query) => $query->where('is_active', true))
            ->with(['team', 'slaPolicy.targets'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $defaultPolicy = SlaPolicy::findDefault()?->load('targets');

        return Inertia::render('Portal/Tickets/Create', [
            'categories' => $categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'icon' => $category->icon->value,
                'team' => [
                    'id' => $category->team->id,
                    'name' => $category->team->name,
                    'type' => $category->team->type->value,
                ],
                'first_response_minutes' => ($category->slaPolicy ?? $defaultPolicy)?->targets
                    ->mapWithKeys(fn (SlaTarget $target): array => [$target->priority->value => $target->first_response_minutes]),
            ])->all(),
            'attachmentLimits' => TicketAttachment::limits(),
            'priorities' => array_map(fn (TicketPriority $priority): string => $priority->value, [
                TicketPriority::Low,
                TicketPriority::Medium,
                TicketPriority::High,
            ]),
        ]);
    }

    /**
     * Open a ticket.
     */
    public function store(StoreTicketRequest $request, CreateTicket $createTicket): RedirectResponse
    {
        $ticket = $createTicket->handle($request->user(), $request->validated());

        return to_route('portal.tickets.show', $ticket)->with('success', __('Ticket created.'));
    }

    /**
     * Display a ticket and the part of its conversation the requester may read.
     */
    public function show(Request $request, Ticket $ticket, MarkTicketNotificationsAsRead $markNotificationsAsRead): Response
    {
        Gate::authorize('view', $ticket);

        $markNotificationsAsRead->handle($request->user(), $ticket);

        $ticket->load([
            'team',
            'category',
            'assignee',
            'closedBy',
            'rating',
            'messages' => fn ($query) => $query->visibleToRequester()->with(['author', 'attachments'])->oldest()->orderBy('id'),
        ]);

        return Inertia::render('Portal/Tickets/Show', [
            'ticket' => TicketResource::make($ticket),
            'attachmentLimits' => TicketAttachment::limits(),
            'can' => [
                'reply' => Gate::allows('reply', $ticket),
                'close' => Gate::allows('close', $ticket),
                'reopen' => Gate::allows('reopen', $ticket),
                'archive' => Gate::allows('archive', $ticket),
                'unarchive' => Gate::allows('unarchive', $ticket),
                'rate' => Gate::allows('rate', $ticket),
            ],
        ]);
    }
}
