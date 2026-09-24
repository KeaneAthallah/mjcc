const TOKEN_KEY = 'mjcc:token';
const USER_KEY = 'mjcc:user';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    role: string;
    responder_type: string | null;
    responder_type_label: string | null;
    email_verified: boolean;
}

type SessionListener = (token: string | null, user: AuthUser | null) => void;

const listeners = new Set<SessionListener>();

function readStorage(key: string): string | null {
    try {
        return localStorage.getItem(key);
    } catch {
        return null;
    }
}

function writeStorage(key: string, value: string | null): void {
    try {
        if (value === null) {
            localStorage.removeItem(key);
        } else {
            localStorage.setItem(key, value);
        }
    } catch {
        // storage unavailable (private mode etc.): in-memory session only
    }
}

export function getToken(): string | null {
    return readStorage(TOKEN_KEY);
}

export function getStoredUser(): AuthUser | null {
    const raw = readStorage(USER_KEY);

    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw) as AuthUser;
    } catch {
        return null;
    }
}

/**
 * Persist the authenticated session and notify every subscriber (the realtime
 * provider rebuilds its Reverb/Echo connection when the token changes).
 */
export function setSession(token: string, user: AuthUser): void {
    writeStorage(TOKEN_KEY, token);
    writeStorage(USER_KEY, JSON.stringify(user));
    listeners.forEach((listener) => listener(token, user));
}

export function clearSession(): void {
    writeStorage(TOKEN_KEY, null);
    writeStorage(USER_KEY, null);
    listeners.forEach((listener) => listener(null, null));
}

export function subscribeToSession(listener: SessionListener): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
}