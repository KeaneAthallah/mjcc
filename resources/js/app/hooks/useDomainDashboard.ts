import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';
import { useRealtimeChannel } from '../lib/realtime';

export type DomainKey = 'education' | 'security' | 'health';

/**
 * Loads a domain dashboard once per (domain, kecamatan) pair, refreshing via
 * `MasterDataChanged` broadcasts — no polling.
 */
export function useDomainDashboard<T>(domain: DomainKey, kecamatanId: number | null): ReturnType<typeof useQuery<T>> {
    const query = useQuery<T>({
        queryKey: ['dashboard', domain, kecamatanId],
        queryFn: () => {
            const suffix = kecamatanId ? `?kecamatan_id=${kecamatanId}` : '';

            return api.get<T>(`/dashboard/${domain}${suffix}`);
        },
        staleTime: Infinity,
    });

    useRealtimeChannel('dashboard', {
        'master-data.changed': () => {
            void query.refetch();
        },
    });

    return query;
}