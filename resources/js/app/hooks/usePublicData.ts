import { useQuery } from '@tanstack/react-query';
import { api } from '../lib/api';
import { useRealtimeChannel } from '../lib/realtime';
import type { PublicCategory, PublicSource, PublicSourceDetail } from '../types/public-data';

export function usePublicCategories(): ReturnType<typeof useQuery<PublicCategory[]>> {
    const query = useQuery<PublicCategory[]>({
        queryKey: ['public-data', 'categories'],
        queryFn: () => api.get<PublicCategory[]>('/public-data/categories'),
        staleTime: 5 * 60_000,
    });

    useRealtimeChannel('dashboard', {
        'public-data.sync-completed': () => {
            void query.refetch();
        },
    });

    return query;
}

export function usePublicSources(category: string): ReturnType<typeof useQuery<PublicSource[]>> {
    const query = useQuery<PublicSource[]>({
        queryKey: ['public-data', 'sources', category],
        queryFn: () => {
            const suffix = category ? `?category=${encodeURIComponent(category)}` : '';

            return api.get<PublicSource[]>(`/public-data/sources${suffix}`);
        },
        staleTime: 5 * 60_000,
    });

    useRealtimeChannel('dashboard', {
        'public-data.sync-completed': () => {
            void query.refetch();
        },
    });

    return query;
}

export function usePublicSource(key: string): ReturnType<typeof useQuery<PublicSourceDetail>> {
    return useQuery<PublicSourceDetail>({
        queryKey: ['public-data', 'source', key],
        queryFn: () => api.get<PublicSourceDetail>(`/public-data/sources/${encodeURIComponent(key)}`),
        staleTime: 5 * 60_000,
    });
}