import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';
import { getStoredUser } from '../lib/auth';
import { useRealtimeChannel } from '../lib/realtime';
import type { AlertFilters, CommandAlertsResponse } from '../types/alerts';

function canWatchCommandCenter(): boolean {
    const user = getStoredUser();

    return user?.role === 'admin' || user?.role === 'operator' || Boolean(user?.responder_type);
}

/**
 * Loads persisted command alerts with their filterable list + counts. The
 * list refreshes over the command-center channel when operators act on an
 * alert (CommandAlertChanged) — no polling.
 */
export function useCommandAlerts(filters: AlertFilters): ReturnType<typeof useQuery<CommandAlertsResponse>> {
    const query = useQuery<CommandAlertsResponse>({
        queryKey: ['alerts', filters],
        queryFn: () => {
            const params = new URLSearchParams({ per_page: '50' });

            if (filters.severity) {
                params.set('severity', filters.severity);
            }
            if (filters.status) {
                params.set('status', filters.status);
            }
            if (filters.kecamatan) {
                params.set('kecamatan', String(filters.kecamatan));
            }
            if (filters.search.trim()) {
                params.set('search', filters.search.trim());
            }

            return api.get<CommandAlertsResponse>(`/alerts?${params.toString()}`);
        },
        staleTime: 30_000,
    });

    const watchLive = canWatchCommandCenter();

    useRealtimeChannel(watchLive ? 'command-center' : '', {
        'command-alert.changed': () => {
            void query.refetch();
        },
    });

    return query;
}