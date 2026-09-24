import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';
import { getStoredUser } from '../lib/auth';
import { useRealtimeChannel } from '../lib/realtime';
import type { NotificationItem } from '../types/notifications';

const NOTIFICATIONS_KEY = ['notifications'];

function loadNotifications(): Promise<NotificationItem[]> {
    return api.get<NotificationItem[]>('/notifications?per_page=25');
}

/**
 * Loads the current user's notifications once and keeps them fresh through the
 * `user.{id}` private channel (NotificationCreated) — no polling. Unread count
 * is derived from the fetched list.
 */
export function useNotifications(): ReturnType<typeof useQuery<NotificationItem[]>> & { unreadCount: number } {
    const user = getStoredUser();
    const query = useQuery<NotificationItem[]>({
        queryKey: NOTIFICATIONS_KEY,
        queryFn: loadNotifications,
        staleTime: 60_000,
    });

    useRealtimeChannel(user ? `user.${user.id}` : '', {
        'notification.created': () => {
            void query.refetch();
        },
    });

    return Object.assign(query, { unreadCount: (query.data ?? []).filter((item) => item.read_at === null).length });
}

export function useMarkNotificationRead(): ReturnType<typeof useMutation<void, unknown, number>> {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (id: number) => api.post<void>(`/notifications/${id}/read`),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_KEY });
        },
    });
}