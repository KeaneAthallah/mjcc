import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';
import { useRealtimeChannel } from '../lib/realtime';
import type { MapFilters, MapResponse } from '../types/maps';

function buildParams(filters: MapFilters): string {
    const params = new URLSearchParams();

    if (filters.sector !== '') {
        params.set('sector', filters.sector);
    }

    if (filters.type !== '') {
        params.set('type', filters.type);
    }

    if (filters.kecamatanId !== null) {
        params.set('kecamatan_id', String(filters.kecamatanId));
    }

    const query = params.toString();

    return query === '' ? '/maps' : `/maps?${query}`;
}

/**
 * Fetches map markers and the kecamatan list, refreshing on MasterDataChanged.
 */
export function useMapData(filters: MapFilters): ReturnType<typeof useQuery<MapResponse>> {
    const query = useQuery<MapResponse>({
        queryKey: ['maps', filters.sector, filters.type, filters.kecamatanId],
        queryFn: () => api.get<MapResponse>(buildParams(filters)),
        staleTime: Infinity,
    });

    useRealtimeChannel('dashboard', {
        'master-data.changed': () => {
            void query.refetch();
        },
    });

    return query;
}