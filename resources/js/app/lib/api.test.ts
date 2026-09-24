import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { api, ApiError } from './api';
import { getToken, setSession } from './auth';

function mockFetch(status: number, payload: unknown): void {
    vi.stubGlobal(
        'fetch',
        vi.fn(async () =>
            new Response(JSON.stringify(payload), {
                status,
                statusText: status >= 200 && status < 300 ? 'OK' : 'Error',
                headers: { 'Content-Type': 'application/json' },
            }),
        ),
    );
}

describe('api client', () => {
    beforeEach(() => {
        localStorage.clear();
        vi.restoreAllMocks();
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('returns the envelope data on success', async () => {
        mockFetch(200, { success: true, message: 'Ok', data: { name: 'Morowali' } });

        await expect(api.get<{ name: string }>('/dashboard')).resolves.toEqual({ name: 'Morowali' });

        const [, init] = vi.mocked(fetch).mock.calls[0];
        expect(init?.headers).toMatchObject({ Accept: 'application/json' });
    });

    it('attaches the bearer token when a session exists', async () => {
        setSession('token-123', {
            id: 1,
            name: 'A',
            email: 'a@b.c',
            role: 'admin',
            responder_type: null,
            responder_type_label: null,
            email_verified: true,
        });
        mockFetch(200, { success: true, message: 'Ok', data: null });

        await api.get('/dashboard');

        const [, init] = vi.mocked(fetch).mock.calls[0];
        expect(init?.headers).toMatchObject({ Authorization: 'Bearer token-123' });
    });

    it('clears the session and dispatches the unauthorized event on 401', async () => {
        const listener = vi.fn();
        window.addEventListener('mjcc:unauthorized', listener);
        mockFetch(401, { success: false, message: 'Unauthenticated.' });

        await expect(api.get('/dashboard')).rejects.toBeInstanceOf(ApiError);

        expect(getToken()).toBeNull();
        expect(listener).toHaveBeenCalledTimes(1);
        window.removeEventListener('mjcc:unauthorized', listener);
    });

    it('exposes validation errors and extra fields', async () => {
        mockFetch(403, {
            success: false,
            message: 'Verifikasi email diperlukan.',
            errors: null,
            verification_required: true,
        });

        const promise = api.get('/dashboard');

        await expect(promise).rejects.toMatchObject({
            status: 403,
            message: 'Verifikasi email diperlukan.',
            errors: null,
            meta: { verification_required: true },
        });
    });
});