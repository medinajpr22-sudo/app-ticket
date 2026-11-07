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
     * An agent answers the requester or leaves a note only IT can read.
     */
    public function store(StoreTicketMessageRequest $request, Ticket $ticket, ReplyToTicket $replyToTicket): RedirectResponse
    {
        $isInternalNote = $request->boolean('is_internal');

        Gate::authorize($isInternalNote ? 'addInternalNote' : 'respond', $ticket);

        $replyToTicket->handle($ticket, $request->user(), $request->validated('body'), $isInternalNote);

        return to_route('desk.tickets.show', $ticket)
            ->with('success', $isInternalNote ? __('Internal note saved.') : __('Message sent.'));
    }
}
