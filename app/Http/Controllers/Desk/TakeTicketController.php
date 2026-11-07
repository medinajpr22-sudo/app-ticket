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
     * The agent confirms taking the ticket, from nobody or from the colleague
     * they saw on the screen. Only then can they write in it.
     */
    public function __invoke(Request $request, Ticket $ticket, TakeTicket $takeTicket): RedirectResponse
    {
        Gate::authorize('take', $ticket);

        $validated = $request->validate([
            'assignee_id' => ['nullable', 'integer'],
        ]);

        $takeTicket->handle($ticket, $request->user(), $validated['assignee_id'] ?? null);

        return to_route('desk.tickets.show', $ticket)->with('success', __('Ticket taken.'));
    }
}
