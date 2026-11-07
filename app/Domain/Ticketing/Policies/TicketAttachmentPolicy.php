<?php

namespace App\Domain\Ticketing\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\TicketAttachment;
use Illuminate\Support\Facades\Gate;

class TicketAttachmentPolicy
{
    /**
     * Whoever can read the message can download its files. Files of internal
     * notes stay with IT, exactly like the notes themselves.
     */
    public function download(User $user, TicketAttachment $attachment): bool
    {
        $message = $attachment->message;
        $ticket = $message->ticket;

        if (! $message->type->isVisibleToRequester()) {
            return Gate::forUser($user)->allows('viewInDesk', $ticket);
        }

        return Gate::forUser($user)->any(['view', 'viewInDesk'], $ticket);
    }
}
