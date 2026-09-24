import { clearSession, getToken } from './auth';

export interface ApiEnvelope<T> {
    success: boolean;
    message: string;
    data: T;
    errors?: ApiErrors | null;
    meta?: Record<string, unknown>;
    [key: string]: unknown;
}

export type ApiErrors = Record<string, string[]>;

export class ApiError extends Error {
    readonly status: number;
    readonly errors: ApiErrors | null;
    /** Non-standard top-level payload fields (e.g. `verification_required`). */
    readonly meta: Record<string, unknown> | null;

    constructor(
        status: number,
        message: string,
        errors: ApiErrors | null = null,
        meta: Record<string, unknown> | null = null,
    ) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
        this.meta = meta;
    }
}

const UNAUTHORIZED_EVENT = 'mjcc:unauthorized';

export function onUnauthorized(listener: () => void): () => void {
    window.addEventListener(UNAUTHORIZED_EVENT, listener);

    return () => window.removeEventListener(UNAUTHORIZED_EVENT, listener);
}

const RESERVED_KEYS = new Set(['success', 'message', 'data', 'errors', 'meta']);

function extractExtra(payload: Record<string, unknown>): Record<string, unknown> {
    const extra: Record<string, unknown> = {};

    for (const [key, value] of Object.entries(payload)) {
        if (!RESERVED_KEYS.has(key)) {
            extra[key] = value;
        }
    }

    return extra;
}

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
    const headers: Record<string, string> = { Accept: 'application/json' };
    const token = getToken();

    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(`/api/v1${path}`, {
        method,
        headers,
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    let payload: ApiEnvelope<T> | null = null;

    try {
        payload = await response.json();
    } catch {
        // non-JSON body (e.g. HTML error page)
    }

    if (!response.ok) {
        if (response.status === 401) {
            clearSession();
            window.dispatchEvent(new Event(UNAUTHORIZED_EVENT));
        }

        throw new ApiError(
            response.status,
            payload?.message ?? response.statusText ?? 'Terjadi kesalahan.',
            payload?.errors ?? null,
            payload ? extractExtra(payload) : null,
        );
    }

    if (!payload) {
        throw new ApiError(response.status, 'Respons kosong dari server.');
    }

    return payload.data;
}

export const api = {
    get: <T>(path: string): Promise<T> => request<T>('GET', path),
    post: <T>(path: string, body?: unknown): Promise<T> => request<T>('POST', path, body),
    put: <T>(path: string, body?: unknown): Promise<T> => request<T>('PUT', path, body),
    delete: <T>(path: string): Promise<T> => request<T>('DELETE', path),
};