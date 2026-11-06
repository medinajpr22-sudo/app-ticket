import type { IconName } from '@/Components/ui/Icon';

export interface Option<TValue extends string = string> {
    value: TValue;
    label: string;
}

export interface DescribedOption<TValue extends string = string>
    extends Option<TValue> {
    description: string;
}

export type RoleSlug = 'super_admin' | 'admin' | 'user';

export type TeamTypeValue = 'general_support' | 'application_support';

export interface Role {
    id: number;
    slug: RoleSlug;
    label: string;
    description: string;
    is_staff: boolean;
}

export interface Department {
    id: number;
    name: string;
}

export interface Location {
    id: number;
    name: string;
}

export interface UserTeam {
    id: number;
    name: string;
    icon: IconName;
    is_lead: boolean;
}

export interface User {
    id: number;
    name: string;
    email: string;
    initials: string;
    job_title: string | null;
    phone: string | null;
    is_active: boolean;
    email_verified_at: string | null;
    last_login_at: string | null;
    role?: Role;
    department?: Department | null;
    location?: Location | null;
    teams?: UserTeam[];
}

export interface TeamMember {
    id: number;
    name: string;
    initials: string;
    is_lead: boolean;
}

export interface Team {
    id: number;
    name: string;
    type: Option<TeamTypeValue>;
    description: string | null;
    icon: IconName;
    is_active: boolean;
    members?: TeamMember[];
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<TItem> {
    data: TItem[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        per_page: number;
        links: PaginationLink[];
    };
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        permissions: string[];
    };
    flash: {
        success: string | null;
    };
};
