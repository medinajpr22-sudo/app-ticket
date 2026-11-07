<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TicketClosureController extends Controller
{
    /**
     * The requester says the problem is solved and closes the ticket.
     * The closing is recorded with their name and the time.
     */
    public function store(Request $request, Ticket $ticket, ChangeTicketStatus $changeStatus): RedirectResponse
    {
        Gate::authorize('close', $ticket);

        $changeStatus->handle($ticket, TicketStatus::Closed, $request->user());

        return to_route('portal.tickets.show', $ticket)->with('success', __('Ticket closed.'));
    }
}
