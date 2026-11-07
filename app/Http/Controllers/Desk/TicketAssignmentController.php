<?php

namespace App\Http\Controllers\Desk;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Team;
use App\Domain\Ticketing\Actions\AssignTicket;
use App\Domain\Ticketing\Actions\TransferTicket;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Desk\AssignTicketRequest;
use App\Http\Requests\Desk\TransferTicketRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketAssignmentController extends Controller
{
    /**
     * Hand the ticket to another agent of the same team.
     */
    public function update(AssignTicketRequest $request, Ticket $ticket, AssignTicket $assignTicket): RedirectResponse
    {
        Gate::authorize('assign', $ticket);

        $assignTicket->handle(
            $ticket,
            User::query()->findOrFail($request->integer('assignee_id')),
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('desk.tickets.show', $ticket)->with('success', __('Ticket reassigned.'));
    }

    /**
     * Send the ticket to another team.
     */
    public function transfer(TransferTicketRequest $request, Ticket $ticket, TransferTicket $transferTicket): RedirectResponse
    {
        Gate::authorize('transfer', $ticket);

        $transferTicket->handle(
            $ticket,
            Team::query()->findOrFail($request->integer('team_id')),
            $request->user(),
            $request->validated('reason'),
        );

        return to_route('desk.tickets.index')->with('success', __('Ticket transferred.'));
    }
}
