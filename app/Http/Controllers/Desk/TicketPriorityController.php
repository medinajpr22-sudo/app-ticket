<?php

namespace App\Http\Controllers\Desk;

use App\Domain\Ticketing\Actions\ChangeTicketPriority;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Desk\UpdateTicketPriorityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketPriorityController extends Controller
{
    /**
     * Reclassify the ticket; its SLA deadlines follow the new priority.
     */
    public function update(UpdateTicketPriorityRequest $request, Ticket $ticket, ChangeTicketPriority $changePriority): RedirectResponse
    {
        Gate::authorize('changePriority', $ticket);

        $changePriority->handle(
            $ticket,
            TicketPriority::from($request->validated('priority')),
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('desk.tickets.show', $ticket)->with('success', __('Priority updated.'));
    }
}
