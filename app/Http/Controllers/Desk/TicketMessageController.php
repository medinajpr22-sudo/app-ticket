<?php

namespace App\Http\Controllers\Desk;

use App\Domain\Ticketing\Actions\ReplyToTicket;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Desk\StoreTicketMessageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketMessageController extends Controller
{
    /**
     * The agent who has the ticket answers the requester.
     */
    public function store(StoreTicketMessageRequest $request, Ticket $ticket, ReplyToTicket $replyToTicket): RedirectResponse
    {
        Gate::authorize('respond', $ticket);

        $replyToTicket->handle(
            $ticket,
            $request->user(),
            $request->validated('body'),
            $request->validated('attachments', []),
        );

        return to_route('desk.tickets.show', $ticket)->with('success', __('Message sent.'));
    }
}
