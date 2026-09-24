import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';
import { useRealtimeChannel } from '../lib/realtime';
import type { DashboardOverview } from '../types/dashboard';

const DASHBOARD_QUERY_KEY = ['dashboard', 'overview'];

/**
 * Loads the overview once and keeps it fresh through realtime broadcasts,
 * replacing the old polling-based refresh.
 */
export function useDashboardOverview(): ReturnType<typeof useQuery<DashboardOverview>> {
    const query = useQuery<DashboardOverview>({
        queryKey: DASHBOARD_QUERY_KEY,
        queryFn: () => api.get<DashboardOverview>('/dashboard'),
        staleTime: Infinity,
    });

    useRealtimeChannel('dashboard', {
        'master-data.changed': () => {
            void query.refetch();
        },
    });

    return query;
}