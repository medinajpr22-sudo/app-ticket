<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Ticketing\Actions\ArchiveTicket;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TicketArchiveController extends Controller
{
    /**
     * The requester takes the ticket off their list.
     */
    public function store(Request $request, Ticket $ticket, ArchiveTicket $archiveTicket): RedirectResponse
    {
        Gate::authorize('archive', $ticket);

        $archiveTicket->handle($ticket, $request->user());

        return to_route('portal.tickets.index')->with('success', __('Ticket removed from your list.'));
    }

    /**
     * The requester puts the ticket back on their list.
     */
    public function destroy(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('unarchive', $ticket);

        $ticket->forceFill(['requester_archived_at' => null])->save();

        return to_route('portal.tickets.show', $ticket)->with('success', __('Ticket back on your list.'));
    }
}
