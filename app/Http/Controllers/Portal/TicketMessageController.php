<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Ticketing\Actions\ReplyToTicket;
use App\Domain\Ticketing\Models\Ticket;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreTicketMessageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketMessageController extends Controller
{
    /**
     * The requester answers in the conversation.
     */
    public function store(StoreTicketMessageRequest $request, Ticket $ticket, ReplyToTicket $replyToTicket): RedirectResponse
    {
        Gate::authorize('reply', $ticket);

        $replyToTicket->handle($ticket, $request->user(), $request->validated('body'));

        return to_route('portal.tickets.show', $ticket)->with('success', __('Message sent.'));
    }
}
