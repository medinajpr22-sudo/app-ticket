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

    public function resolve(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $ticket->status->isOpen();
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $ticket->status === TicketStatus::Resolved;
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

    public function take(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(PermissionSlug::TakeTickets)
            && $user->belongsToTeam($ticket->team_id)
            && $ticket->status === TicketStatus::New
            && $ticket->assignee_id === null;
    }

    /**
     * Answering the requester requires taking the ticket first, so an answered
     * ticket always has someone responsible for it. Internal notes do not.
     */
    public function respond(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(PermissionSlug::ReplyToTickets)
            && $user->belongsToTeam($ticket->team_id)
            && $ticket->status !== TicketStatus::New
            && $this->acceptsMessages($ticket);
    }

    public function addInternalNote(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(PermissionSlug::AddInternalNotes) && $user->belongsToTeam($ticket->team_id);
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission(PermissionSlug::ChangeTicketStatus)
            && $user->belongsToTeam($ticket->team_id)
            && $ticket->status !== TicketStatus::Closed;
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
     * Closed and cancelled tickets keep their conversation as it was.
     */
    private function acceptsMessages(Ticket $ticket): bool
    {
        return ! in_array($ticket->status, [TicketStatus::Closed, TicketStatus::Cancelled], true);
    }
}
