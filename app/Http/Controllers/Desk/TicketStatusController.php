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
     * Move the ticket to another status. Closing it is reserved to the agent
     * in charge of it.
     */
    public function update(UpdateTicketStatusRequest $request, Ticket $ticket, ChangeTicketStatus $changeStatus): RedirectResponse
    {
        $status = TicketStatus::from($request->validated('status'));

        Gate::authorize($status === TicketStatus::Closed ? 'close' : 'changeStatus', $ticket);

        $changeStatus->handle($ticket, $status, $request->user());

        return to_route('desk.tickets.show', $ticket)->with('success', __('Status updated.'));
    }
}
