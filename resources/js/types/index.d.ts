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

export type TicketPriorityValue = 'critical' | 'high' | 'medium' | 'low';

export type TicketTypeValue = 'incident' | 'service_request';

export interface BusinessHour {
    weekday: number;
    label: string;
    opens_at: string;
    closes_at: string;
}

export interface Holiday {
    id: number;
    name: string;
    date: string;
}

export interface BusinessCalendar {
    id: number;
    name: string;
    timezone: string;
    is_default: boolean;
    hours?: BusinessHour[];
    holidays?: Holiday[];
    holidays_count?: number;
}

export interface SlaTarget {
    priority: TicketPriorityValue;
    label: string;
    first_response_minutes: number;
    resolution_minutes: number;
}

export interface SlaPolicy {
    id: number;
    business_calendar_id: number;
    name: string;
    description: string | null;
    is_default: boolean;
    business_calendar?: BusinessCalendar;
    targets?: SlaTarget[];
    categories_count?: number;
}

export interface Category {
    id: number;
    team_id: number;
    sla_policy_id: number | null;
    name: string;
    description: string | null;
    icon: IconName;
    default_ticket_type: Option<TicketTypeValue>;
    is_visible: boolean;
    sort_order: number;
    team?: Team;
    sla_policy?: SlaPolicy | null;
}

export type MessageTemplatePurposeValue =
    | 'greeting'
    | 'acknowledgement'
    | 'auto_close'
    | 'canned_response';

export interface MessageTemplatePurposeOption
    extends DescribedOption<MessageTemplatePurposeValue> {
    is_automatic: boolean;
}

export interface TemplateVariableOption {
    name: string;
    token: string;
    label: string;
    sample: string;
    purposes: MessageTemplatePurposeValue[];
}

export interface MessageTemplate {
    id: number;
    team_id: number | null;
    purpose: Option<MessageTemplatePurposeValue>;
    is_automatic: boolean;
    name: string;
    body: string;
    is_active: boolean;
    team?: Team | null;
    author_name?: string | null;
    updated_at: string | null;
}

export type TicketStatusValue =
    | 'received'
    | 'in_progress'
    | 'waiting_on_requester'
    | 'resolved'
    | 'closed';

export type SlaStateValue = 'on_track' | 'at_risk' | 'breached' | 'paused' | 'met' | 'missed';

export type TicketMessageTypeValue = 'reply' | 'internal_note' | 'automated';

export interface TicketAttachment {
    id: number;
    name: string;
    size_bytes: number;
    url: string;
    preview_url: string | null;
}

export interface AttachmentLimits {
    max_files: number;
    max_kilobytes: number;
    extensions: string[];
}

export interface TicketMessage {
    id: number;
    type: TicketMessageTypeValue;
    body: string;
    created_at: string;
    author: { id: number; name: string; initials: string } | null;
    attachments?: TicketAttachment[];
}

export interface TicketRating {
    score: number;
    comment: string | null;
    created_at: string;
}

export interface TicketSla {
    state: Option<SlaStateValue>;
    target: 'first_response' | 'resolution';
    due_at: string;
}

export interface Ticket {
    id: number;
    subject: string;
    status: Option<TicketStatusValue>;
    is_open: boolean;
    priority: Option<TicketPriorityValue>;
    type: Option<TicketTypeValue>;
    channel: string;
    created_at: string;
    updated_at: string | null;
    first_responded_at: string | null;
    resolved_at: string | null;
    closed_at: string | null;
    closed_by?: { id: number; name: string } | null;
    archived_at: string | null;
    requester?: User;
    assignee?: User | null;
    team?: { id: number; name: string; icon: IconName };
    category?: { id: number; name: string; icon: IconName };
    sla?: TicketSla;
    messages?: TicketMessage[];
    rating?: TicketRating | null;
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
    notifications: {
        unread_count: number;
        recent: AppNotification[];
    } | null;
    flash: {
        success: string | null;
    };
};

export interface AppNotification {
    id: string;
    title: string;
    body: string;
    excerpt: string | null;
    ticket_id: number | null;
    read_at: string | null;
    created_at: string;
}

/**
 * Numbers about a group of tickets. Times are business minutes; shares are
 * percentages. Null means there is nothing to measure yet.
 */
export interface TicketMetrics {
    total: number;
    received: number;
    in_progress: number;
    waiting: number;
    open: number;
    resolved: number;
    resolution_compliance: number | null;
    first_response_compliance: number | null;
    average_first_response_minutes: number | null;
    average_resolution_minutes: number | null;
    average_rating: number | null;
    ratings: number;
}

export interface TicketBreakdown {
    id: number | null;
    label: string;
    total: number;
}

export interface ReportPeriod {
    from: string;
    to: string;
}
