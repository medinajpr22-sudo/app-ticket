<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;

class MarkTicketNotificationsAsRead
{
    /**
     * Opening a ticket counts as reading everything it notified the person about.
     */
    public function handle(User $user, Ticket $ticket): int
    {
        return $user->unreadNotifications()
            ->where('data->ticket_id', $ticket->id)
            ->update(['read_at' => now()]);
    }
}
