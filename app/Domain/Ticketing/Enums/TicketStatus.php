<?php

namespace App\Domain\Ticketing\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

enum TicketStatus: string
{
    use HasTranslatedLabel;

    case New = 'new';
    case InProgress = 'in_progress';
    case WaitingOnRequester = 'waiting_on_requester';
    case WaitingOnThirdParty = 'waiting_on_third_party';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    /**
     * The ticket still needs work from someone.
     */
    public function isOpen(): bool
    {
        return in_array($this, self::open(), true);
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
        return [self::New, self::InProgress, self::WaitingOnRequester, self::WaitingOnThirdParty];
    }

    /**
     * Statuses an agent can move a ticket to by hand. New is reached by
     * creating a ticket and Closed by the scheduled closing of resolved ones.
     *
     * @return list<self>
     */
    public static function agentChoices(): array
    {
        return [self::InProgress, self::WaitingOnRequester, self::WaitingOnThirdParty, self::Resolved, self::Cancelled];
    }
}
