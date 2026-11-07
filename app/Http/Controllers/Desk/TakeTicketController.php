<?php

namespace App\Http\Controllers\Desk;

use App\Domain\Ticketing\Actions\TakeTicket;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TakeTicketController extends Controller
{
    /**
     * The agent takes the ticket; the requester gets the team's greeting.
     */
    public function __invoke(Request $request, Ticket $ticket, TakeTicket $takeTicket): RedirectResponse
    {
        Gate::authorize('take', $ticket);

        $takeTicket->handle($ticket, $request->user());

        return to_route('desk.tickets.show', $ticket)->with('success', __('Ticket taken.'));
    }
}
