<?php

namespace App\Domain\Ticketing\Enums;

/**
 * What happened in a ticket that someone should hear about.
 */
enum TicketActivity: string
{
    /**
     * IT wrote to the requester.
     */
    case NewMessage = 'new_message';

    /**
     * The requester wrote to the agent who has the ticket.
     */
    case RequesterReplied = 'requester_replied';

    case WaitingOnRequester = 'waiting_on_requester';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Transferred = 'transferred';

    public function title(): string
    {
        return match ($this) {
            self::NewMessage => __('You have a new message'),
            self::RequesterReplied => __('The requester replied'),
            default => __('Your ticket was updated'),
        };
    }

    /**
     * The sentence that explains it.
     *
     * @param  string|null  $actor  Who did it; null when the system did.
     */
    public function describe(int $ticketNumber, ?string $actor, string $teamName): string
    {
        $replace = ['number' => $ticketNumber, 'name' => $actor ?? __('IT'), 'team' => $teamName];

        return match ($this) {
            self::NewMessage => __('Your ticket #:number has a new reply from :name.', $replace),
            self::RequesterReplied => __(':name replied on ticket #:number.', $replace),
            self::WaitingOnRequester => __('We need your answer to continue with ticket #:number.', $replace),
            self::Resolved => __(':name marked ticket #:number as resolved. Close it if everything is fine, or reopen it.', $replace),
            self::Closed => __('Ticket #:number was closed by :name.', $replace),
            self::Transferred => __('Your ticket #:number moved to the :team team.', $replace),
        };
    }

    /**
     * The activity a status change means for the requester, if any.
     */
    public static function forStatus(TicketStatus $status): ?self
    {
        return match ($status) {
            TicketStatus::WaitingOnRequester => self::WaitingOnRequester,
            TicketStatus::Resolved => self::Resolved,
            TicketStatus::Closed => self::Closed,
            default => null,
        };
    }
}
