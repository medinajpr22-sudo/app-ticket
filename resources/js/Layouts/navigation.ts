import { IconName } from '@/Components/ui/Icon';

export interface NavigationItem {
    label: string;
    routeName: string;
    activePatterns: string[];
    icon: IconName;
    /** Every one of them is needed. */
    permissions?: string[];
    /** Belonging to a team, or leading one, on top of the permissions. */
    requires?: 'team' | 'lead';
}

export interface NavigationSection {
    label: string;
    items: NavigationItem[];
}

/**
 * Sidebar entries. An item with permissions is only shown to users who have
 * them all; the server enforces the same rules on the route.
 */
export const navigation: NavigationSection[] = [
    {
        label: 'General',
        items: [
            {
                label: 'Estadísticas',
                routeName: 'reports.overview',
                activePatterns: ['reports.*'],
                icon: 'chart',
                permissions: ['reports.view', 'tickets.view_all'],
            },
        ],
    },
    {
        label: 'Soporte',
        items: [
            {
                label: 'Mis tickets',
                routeName: 'portal.tickets.index',
                activePatterns: ['portal.tickets.index', 'portal.tickets.show'],
                icon: 'ticket',
                permissions: ['tickets.view_own'],
            },
            {
                label: 'Nuevo ticket',
                routeName: 'portal.tickets.create',
                activePatterns: ['portal.tickets.create'],
                icon: 'plus',
                permissions: ['tickets.create'],
            },
        ],
    },
    {
        label: 'Equipo de TI',
        items: [
            {
                label: 'Bandeja',
                routeName: 'desk.tickets.index',
                activePatterns: ['desk.tickets.*'],
                icon: 'inbox',
                permissions: ['tickets.view_team'],
                requires: 'team',
            },
            {
                label: 'Historial',
                routeName: 'desk.history.index',
                activePatterns: ['desk.history.*'],
                icon: 'book',
                permissions: ['tickets.view_team'],
                requires: 'team',
            },
            {
                label: 'Mi equipo',
                routeName: 'desk.teams.index',
                activePatterns: ['desk.teams.*'],
                icon: 'users',
                permissions: ['teams.lead'],
                requires: 'lead',
            },
        ],
    },
    {
        label: 'Administración',
        items: [
            {
                label: 'Usuarios',
                routeName: 'management.users.index',
                activePatterns: ['management.users.*'],
                icon: 'user',
                permissions: ['users.manage'],
            },
            {
                label: 'Equipos',
                routeName: 'management.teams.index',
                activePatterns: ['management.teams.*'],
                icon: 'users',
                permissions: ['teams.manage'],
            },
            {
                label: 'Categorías',
                routeName: 'management.categories.index',
                activePatterns: ['management.categories.*'],
                icon: 'tag',
                permissions: ['categories.manage'],
            },
            {
                label: 'SLA y horarios',
                routeName: 'management.service-levels.index',
                activePatterns: ['management.service-levels.*'],
                icon: 'clock',
                permissions: ['sla_policies.manage'],
            },
            {
                label: 'Plantillas',
                routeName: 'management.message-templates.index',
                activePatterns: ['management.message-templates.*'],
                icon: 'mail',
                permissions: ['message_templates.manage'],
            },
            {
                label: 'Roles y permisos',
                routeName: 'management.roles.index',
                activePatterns: ['management.roles.*'],
                icon: 'shield',
                permissions: ['roles.manage'],
            },
        ],
    },
];
