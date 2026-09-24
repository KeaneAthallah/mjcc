import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { api } from '../lib/api';
import { getStoredUser } from '../lib/auth';
import { useRealtimeChannel } from '../lib/realtime';
import type { SosAlert } from '../types/sos';

const SOS_LIST_KEY = ['sos', 'list'];

function canWatchCommandCenter(): boolean {
    const user = getStoredUser();

    return user?.role === 'admin' || user?.role === 'operator' || Boolean(user?.responder_type);
}

/**
 * Loads the SOS inbox (operators/admins see everyone, viewers only their own)
 * and refreshes live via the command-center channel (SosCreated/SosUpdated) —
 * no polling.
 */
export function useSosAlerts(status: string): ReturnType<typeof useQuery<SosAlert[]>> {
    const query = useQuery<SosAlert[]>({
        queryKey: [SOS_LIST_KEY, status],
        queryFn: () => api.get<SosAlert[]>(status ? `/sos?per_page=50&status=${status}` : '/sos?per_page=50'),
        staleTime: 30_000,
    });

    useRealtimeChannel(canWatchCommandCenter() ? 'command-center' : '', {
        'sos.created': () => {
            void query.refetch();
        },
        'sos.updated': () => {
            void query.refetch();
        },
    });

    return query;
}

/**
 * Live-updating detail for a single SOS alert via the private `sos.{id}`
 * channel. Falls back to a manual refetch for owners who leave the page open.
 */
export function useSosDetail(id: number | null): ReturnType<typeof useQuery<SosAlert>> {
    const query = useQuery<SosAlert>({
        queryKey: ['sos', 'detail', id],
        queryFn: () => api.get<SosAlert>(`/sos/${id}`),
        enabled: id !== null,
        staleTime: 30_000,
    });

    useRealtimeChannel(id !== null ? `sos.${id}` : '', {
        'sos.updated': () => {
            void query.refetch();
        },
    });

    return query;
}

export type SosAction = 'acknowledge' | 'respond' | 'accept' | 'on-the-way' | 'arrived' | 'constraint' | 'resolve' | 'cancel';

function actionPath(sosId: number, action: SosAction, payload?: Record<string, unknown>): Promise<SosAlert> {
    return api.post<SosAlert>(
        `/sos/${sosId}/${action}`,
        Object.keys(payload ?? {}).length > 0 ? { response_message: '', ...payload } : { response_message: '' },
    );
}

/**
 * Runs a SOS workflow action and refreshes the inbox + any open detail query so
 * the live board always reflects the authoritative status.
 */
export function useSosAction(): ReturnType<typeof useMutation<SosAlert, unknown, { sosId: number; action: SosAction; payload?: Record<string, unknown> }>> {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ sosId, action, payload }) => actionPath(sosId, action, payload),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['sos'] });
        },
    });
}

export function canOperateSos(): boolean {
    const user = getStoredUser();

    return user?.role === 'admin' || user?.role === 'operator';
}

export function isResponderUser(): boolean {
    return Boolean(getStoredUser()?.responder_type);
}