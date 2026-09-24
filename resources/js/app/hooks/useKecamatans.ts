import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';
import { useRealtimeChannel } from '../lib/realtime';
import type { KecamatanOption } from '../types/dashboards';

const KECAMATANS_QUERY_KEY = ['kecamatans', 'options'];

/**
 * Lightweight kecamatan name list for dashboard filters. Refreshes whenever
 * master data changes instead of polling.
 */
export function useKecamatans(): ReturnType<typeof useQuery<KecamatanOption[]>> {
    const query = useQuery<KecamatanOption[]>({
        queryKey: KECAMATANS_QUERY_KEY,
        queryFn: () => api.get<KecamatanOption[]>('/kecamatans?per_page=100'),
        staleTime: Infinity,
    });

    useRealtimeChannel('dashboard', {
        'master-data.changed': () => {
            void query.refetch();
        },
    });

    return query;
}