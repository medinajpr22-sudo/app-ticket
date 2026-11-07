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
     * The requester rejects the solution IT proposed: the problem came back.
     * The ticket returns to whoever had it.
     */
    public function destroy(Request $request, Ticket $ticket, ChangeTicketStatus $changeStatus): RedirectResponse
    {
        Gate::authorize('reopen', $ticket);

        $changeStatus->handle($ticket, $ticket->assignee_id ? TicketStatus::InProgress : TicketStatus::Received, $request->user());

        return to_route('portal.tickets.show', $ticket)->with('success', __('Ticket reopened.'));
    }
}
