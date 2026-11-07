<?php

namespace App\Domain\Ticketing\Policies;

use App\Domain\Identity\Enums\PermissionSlug;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;

/**
 * Requesters act on their own tickets from the portal; IT staff act on the
 * tickets of their teams from the desk. Super admins pass through Gate::before.
 */
class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return $ticket->requester()->is($user) && $user->hasPermission(PermissionSlug::ViewOwnTickets);
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $this->acceptsMessages($ticket);
    }

    /**
     * The requester no longer needs help, whether IT already proposed a
     * solution or not, and closes the ticket themselves.
     */
    public function close(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $this->acceptsMessages($ticket);
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $ticket->status === TicketStatus::Resolved;
    }

    /**
     * Take the ticket off the requester's list. It stays in the desk as it is.
     */
    public function archive(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $ticket->requester_archived_at === null;
    }

    public function unarchive(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $ticket->requester_archived_at !== null;
    }

    /**
     * One rating per ticket, once IT finished working on it.
     */
    public function rate(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket)
            && in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true)
            && $ticket->rating()->doesntExist();
    }

    public function viewInDesk(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(PermissionSlug::ViewAllTickets)
            || ($user->hasPermission(PermissionSlug::ViewTeamTickets) && $user->belongsToTeam($ticket->team_id));
    }

    /**
     * Become the person responsible for the ticket: when nobody has it, or
     * when a colleague has it and the agent confirms taking it from them.
     */
    public function take(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(PermissionSlug::TakeTickets)
            && $user->belongsToTeam($ticket->team_id)
            && $ticket->assignee_id !== $user->id
            && $this->acceptsMessages($ticket);
    }

    /**
     * Only the agent who has the ticket writes in it, so every answer has
     * someone responsible behind it. Everyone else takes the ticket first.
     */
    public function respond(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(PermissionSlug::ReplyToTickets)
            && $user->belongsToTeam($ticket->team_id)
            && $ticket->assignee_id === $user->id
            && $this->acceptsMessages($ticket);
    }

    /**
     * A received ticket is taken before anything else happens to it, so its
     * status always reflects someone's work.
     */
    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(PermissionSlug::ChangeTicketStatus)
            && $user->belongsToTeam($ticket->team_id)
            && $ticket->assignee_id !== null
            && $this->acceptsMessages($ticket);
    }

    /**
     * Hand an open ticket to another agent of its team.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(PermissionSlug::AssignTickets)
            && $user->belongsToTeam($ticket->team_id)
            && $ticket->status->isOpen();
    }

    /**
     * Send an open ticket to another team.
     */
    public function transfer(User $user, Ticket $ticket): bool
    {
        return $this->assign($user, $ticket);
    }

    public function changePriority(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(PermissionSlug::ChangeTicketPriority)
            && $user->belongsToTeam($ticket->team_id)
            && $ticket->status->isOpen();
    }

    /**
     * Closed tickets keep their conversation as it was.
     */
    private function acceptsMessages(Ticket $ticket): bool
    {
        return $ticket->status !== TicketStatus::Closed && ! $ticket->status->isRetired();
    }
}
