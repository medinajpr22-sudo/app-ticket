import { IconName } from '@/Components/ui/Icon';

export interface NavigationItem {
    label: string;
    routeName: string;
    activePattern: string;
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
                label: 'Inicio',
                routeName: 'dashboard',
                activePattern: 'dashboard',
                icon: 'home',
            },
        ],
    },
    {
        label: 'Administración',
        items: [
            {
                label: 'Usuarios',
                routeName: 'management.users.index',
                activePattern: 'management.users.*',
                icon: 'user',
                permission: 'users.manage',
            },
            {
                label: 'Equipos',
                routeName: 'management.teams.index',
                activePattern: 'management.teams.*',
                icon: 'users',
                permission: 'teams.manage',
            },
        ],
    },
];
