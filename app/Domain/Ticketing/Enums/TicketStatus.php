<?php

namespace App\Domain\Ticketing\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

enum TicketStatus: string
{
    use HasTranslatedLabel;

    case Received = 'received';
    case InProgress = 'in_progress';
    case WaitingOnRequester = 'waiting_on_requester';
    case Resolved = 'resolved';
    case Closed = 'closed';

    /**
     * @deprecated Retired on 2026-11-09. Tickets in it moved to "in progress";
     *             the case stays so the status history recorded before still loads.
     */
    case WaitingOnThirdParty = 'waiting_on_third_party';

    /**
     * @deprecated Retired on 2026-11-09. Tickets in it moved to "closed";
     *             the case stays so the status history recorded before still loads.
     */
    case Cancelled = 'cancelled';

    /**
     * The ticket still needs work from someone.
     */
    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
    }

    /**
     * No ticket can enter this status anymore; it only appears in old history.
     */
    public function isRetired(): bool
    {
        return in_array($this, self::retired(), true);
    }

    /**
     * The SLA clock stops while the ball is in the requester's court.
     */
    public function pausesSla(): bool
    {
        return $this === self::WaitingOnRequester;
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Received, self::InProgress, self::WaitingOnRequester];
    }

    /**
     * @return list<self>
     */
    public static function retired(): array
    {
        return [self::WaitingOnThirdParty, self::Cancelled];
    }

    /**
     * The statuses a ticket can be in today, in the order of its life.
     *
     * @return list<self>
     */
    public static function current(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status): bool => ! $status->isRetired()));
    }

    /**
     * Statuses an agent can move a ticket to by hand. Received is reached by
     * creating or transferring a ticket, never by choice.
     *
     * @return list<self>
     */
    public static function agentChoices(): array
    {
        return [self::InProgress, self::WaitingOnRequester, self::Resolved, self::Closed];
    }
}
