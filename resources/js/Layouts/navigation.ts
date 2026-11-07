import { IconName } from '@/Components/ui/Icon';

export interface NavigationItem {
    label: string;
    routeName: string;
    activePatterns: string[];
    icon: IconName;
    permission?: string;
}

export interface NavigationSection {
    label: string;
    items: NavigationItem[];
}

/**
 * Sidebar entries. An item with a permission is only shown to users who have it;
 * the server enforces the same permission on the route.
 */
export const navigation: NavigationSection[] = [
    {
        label: 'General',
        items: [
            {
                label: 'Panel general',
                routeName: 'dashboard',
                activePatterns: ['dashboard'],
                icon: 'home',
                permission: 'users.manage',
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
                permission: 'tickets.view_own',
            },
            {
                label: 'Nuevo ticket',
                routeName: 'portal.tickets.create',
                activePatterns: ['portal.tickets.create'],
                icon: 'plus',
                permission: 'tickets.create',
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
                permission: 'tickets.view_team',
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
                permission: 'users.manage',
            },
            {
                label: 'Equipos',
                routeName: 'management.teams.index',
                activePatterns: ['management.teams.*'],
                icon: 'users',
                permission: 'teams.manage',
            },
            {
                label: 'Categorías',
                routeName: 'management.categories.index',
                activePatterns: ['management.categories.*'],
                icon: 'tag',
                permission: 'categories.manage',
            },
            {
                label: 'SLA y horarios',
                routeName: 'management.service-levels.index',
                activePatterns: ['management.service-levels.*'],
                icon: 'clock',
                permission: 'sla_policies.manage',
            },
            {
                label: 'Plantillas',
                routeName: 'management.message-templates.index',
                activePatterns: ['management.message-templates.*'],
                icon: 'mail',
                permission: 'message_templates.manage',
            },
            {
                label: 'Roles y permisos',
                routeName: 'management.roles.index',
                activePatterns: ['management.roles.*'],
                icon: 'shield',
                permission: 'roles.manage',
            },
        ],
    },
];
