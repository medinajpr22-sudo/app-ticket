<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketArchiveController extends Controller
{
    /**
     * The requester takes a closed ticket off their list. Nothing is deleted.
     */
    public function store(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('archive', $ticket);

        $ticket->forceFill(['requester_archived_at' => now()])->save();

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
