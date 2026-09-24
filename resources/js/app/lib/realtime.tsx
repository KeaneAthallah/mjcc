import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import { getToken, subscribeToSession } from './auth';

if (typeof window !== 'undefined') {
    window.Pusher = Pusher;
}

export type ConnectionState = 'unknown' | 'connected' | 'connecting' | 'disconnected';

export type BroadcastEventPayload = Record<string, unknown>;

interface RealtimeContextValue {
    echo: Echo<'reverb'> | null;
    connection: ConnectionState;
}

const RealtimeContext = createContext<RealtimeContextValue>({
    echo: null,
    connection: 'unknown',
});

function createEcho(token: string): Echo<'reverb'> {
    return new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY ?? 'mjcc-reverb-key',
        wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
        wssPort: 443,
        forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        },
    });
}

/**
 * Manages the single Reverb/Echo connection for the whole SPA. The connection
 * is torn down when the session ends so channels never outlive their token.
 */
export function RealtimeProvider({ children }: { children: ReactNode }): React.JSX.Element {
    const [echo, setEcho] = useState<Echo<'reverb'> | null>(null);
    const [connection, setConnection] = useState<ConnectionState>('unknown');

    useEffect(
        () =>
            subscribeToSession((token) => {
                if (token) {
                    return;
                }

                setEcho(null);
                setConnection('unknown');
            }),
        [],
    );

    useEffect(() => {
        const token = getToken();

        if (!token) {
            setEcho(null);
            setConnection('unknown');

            return;
        }

        const instance = createEcho(token);
        const raw = instance.connector.pusher as Pusher;

        const update = (): void => {
            const state: ConnectionState =
                raw.connection.state === 'connected'
                    ? 'connected'
                    : raw.connection.state === 'connecting'
                        ? 'connecting'
                        : 'disconnected';
            setConnection(state);
        };

        [
            'connected',
            'connecting',
            'unavailable',
            'failed',
            'disconnected',
        ].forEach((event) => raw.connection.bind(event, update));

        update();
        setEcho(instance);

        return () => {
            [
                'connected',
                'connecting',
                'unavailable',
                'failed',
                'disconnected',
            ].forEach((event) => raw.connection.unbind(event, update));

            instance.disconnect();
            setEcho(null);
        };
    }, []);

    return (
        <RealtimeContext.Provider value={{ echo, connection }}>
            {children}
        </RealtimeContext.Provider>
    );
}

export function useRealtime(): RealtimeContextValue {
    return useContext(RealtimeContext);
}

export type ChannelHandlers = Record<string, (payload: BroadcastEventPayload) => void>;

/**
 * Subscribe to a private channel and invoke the matching handler for each
 * broadcast. Handlers are rebound whenever their identity changes.
 */
export function useRealtimeChannel(channel: string, handlers: ChannelHandlers): void {
    const { echo } = useRealtime();

    useEffect(() => {
        if (!channel || !echo) {
            return;
        }

        const privateChannel = echo.private(channel);

        for (const [event, handler] of Object.entries(handlers)) {
            privateChannel.listen(event, handler);
        }

        return () => {
            for (const [event] of Object.entries(handlers)) {
                privateChannel.stopListening(event);
            }
        };
    }, [echo, channel, handlers]);
}