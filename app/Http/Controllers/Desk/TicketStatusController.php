<?php

namespace App\Http\Controllers\Desk;

use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Desk\UpdateTicketStatusRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketStatusController extends Controller
{
    /**
     * Move the ticket to another status.
     */
    public function update(UpdateTicketStatusRequest $request, Ticket $ticket, ChangeTicketStatus $changeStatus): RedirectResponse
    {
        Gate::authorize('changeStatus', $ticket);

        $changeStatus->handle($ticket, TicketStatus::from($request->validated('status')), $request->user());

        return to_route('desk.tickets.show', $ticket)->with('success', __('Status updated.'));
    }
}
