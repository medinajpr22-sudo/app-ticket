<?php

namespace App\Domain\Ticketing\Enums;

enum TicketMessageType: string
{
    /**
     * Written by a person and visible to everyone in the ticket.
     */
    case Reply = 'reply';

    /**
     * Only IT staff can read it.
     */
    case InternalNote = 'internal_note';

    /**
     * Sent by the system on its own, such as the acknowledgement of receipt.
     */
    case Automated = 'automated';

    public function isVisibleToRequester(): bool
    {
        return $this !== self::InternalNote;
    }

    /**
     * @return list<self>
     */
    public static function visibleToRequester(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type): bool => $type->isVisibleToRequester()));
    }
}
