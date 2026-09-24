export type SosStatus =
    | 'active'
    | 'acknowledged'
    | 'responding'
    | 'accepted'
    | 'on_the_way'
    | 'arrived'
    | 'constrained'
    | 'resolved'
    | 'cancelled';

export interface SosAlert {
    id: number;
    user_id: number;
    user?: { id: number; name: string; role: string } | null;
    latitude: number;
    longitude: number;
    accuracy: number | null;
    status: SosStatus;
    status_label: string;
    category: string;
    category_label: string;
    message: string;
    response_message: string | null;
    constraint_type: string | null;
    constraint_type_label: string | null;
    constraint_reason: string | null;
    constrained_by: number | null;
    constrained_by_user?: { id: number; name: string } | null;
    constrained_at: string | null;
    responded_by: number | null;
    responded_by_user?: { id: number; name: string } | null;
    responded_at: string | null;
    resolved_by: number | null;
    resolved_by_user?: { id: number; name: string } | null;
    resolved_at: string | null;
    accepted_by: number | null;
    accepted_by_user?: { id: number; name: string } | null;
    accepted_at: string | null;
    created_at: string;
    updated_at: string;
    is_owner: boolean;
    can_manage: boolean;
}

export const SOS_STATUS_COLORS: Record<SosStatus, string> = {
    active: 'red',
    acknowledged: 'amber',
    responding: 'blue',
    accepted: 'blue',
    on_the_way: 'violet',
    arrived: 'teal',
    constrained: 'amber',
    resolved: 'green',
    cancelled: 'gray',
};

export const SOS_OPEN_STATUSES: SosStatus[] = [
    'active',
    'acknowledged',
    'responding',
    'accepted',
    'on_the_way',
    'arrived',
    'constrained',
];

export const SOS_CATEGORY_COLORS: Record<string, string> = {
    general: 'gray',
    medical: 'red',
    fire: 'amber',
    police: 'blue',
};