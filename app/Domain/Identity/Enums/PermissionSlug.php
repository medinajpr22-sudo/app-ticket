<?php

namespace App\Domain\Identity\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

enum PermissionSlug: string
{
    use HasTranslatedLabel;

    case CreateTickets = 'tickets.create';
    case ViewOwnTickets = 'tickets.view_own';
    case ViewTeamTickets = 'tickets.view_team';
    case ViewAllTickets = 'tickets.view_all';
    case ReplyToTickets = 'tickets.reply';
    case AddInternalNotes = 'tickets.add_internal_note';
    case TakeTickets = 'tickets.take';
    case AssignTickets = 'tickets.assign';
    case ChangeTicketStatus = 'tickets.change_status';
    case ViewReports = 'reports.view';
    case ManageUsers = 'users.manage';
    case ManageTeams = 'teams.manage';
    case ManageCategories = 'categories.manage';
    case ManageSlaPolicies = 'sla_policies.manage';
    case ManageMessageTemplates = 'message_templates.manage';
    case ManageRoles = 'roles.manage';
    case ManageIdentityProviders = 'identity_providers.manage';
    case ViewAuditLogs = 'audit_logs.view';

    /**
     * Permissions each role receives when the application is installed.
     *
     * @return list<self>
     */
    public static function defaultsFor(RoleSlug $role): array
    {
        return match ($role) {
            RoleSlug::SuperAdmin => self::cases(),
            RoleSlug::Admin => [
                self::CreateTickets,
                self::ViewOwnTickets,
                self::ViewTeamTickets,
                self::ReplyToTickets,
                self::AddInternalNotes,
                self::TakeTickets,
                self::AssignTickets,
                self::ChangeTicketStatus,
                self::ViewReports,
            ],
            RoleSlug::User => [
                self::CreateTickets,
                self::ViewOwnTickets,
            ],
        };
    }
}
