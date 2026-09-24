export interface NotificationItem {
    id: number;
    user_id: number;
    title: string;
    body: string;
    type: string;
    data: Record<string, unknown> | null;
    read_at: string | null;
    created_at: string;
    updated_at: string;
}