<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TicketResolutionController extends Controller
{
    /**
     * The requester says the problem is solved.
     */
    public function store(Request $request, Ticket $ticket, ChangeTicketStatus $changeStatus): RedirectResponse
    {
        Gate::authorize('resolve', $ticket);

        $changeStatus->handle($ticket, TicketStatus::Resolved, $request->user());

        return to_route('portal.tickets.show', $ticket)->with('success', __('Ticket marked as resolved.'));
    }

    /**
     * The requester says the problem came back. It returns to whoever had it.
     */
    public function destroy(Request $request, Ticket $ticket, ChangeTicketStatus $changeStatus): RedirectResponse
    {
        Gate::authorize('reopen', $ticket);

        $changeStatus->handle($ticket, $ticket->assignee_id ? TicketStatus::InProgress : TicketStatus::New, $request->user());

        return to_route('portal.tickets.show', $ticket)->with('success', __('Ticket reopened.'));
    }
}
