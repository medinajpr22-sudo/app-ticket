<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Ticketing\Actions\RateTicket;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreTicketRatingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketRatingController extends Controller
{
    /**
     * The requester rates the attention once the ticket is solved.
     */
    public function store(StoreTicketRatingRequest $request, Ticket $ticket, RateTicket $rateTicket): RedirectResponse
    {
        Gate::authorize('rate', $ticket);

        $rateTicket->handle($ticket, $request->validated());

        return to_route('portal.tickets.show', $ticket)->with('success', __('Thanks for your rating.'));
    }
}
