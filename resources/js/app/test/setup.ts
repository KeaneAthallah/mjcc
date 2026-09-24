import '@testing-library/jest-dom/vitest';
import { vi } from 'vitest';

// react-leaflet + leaflet need browser map APIs (measurements, DOM layout) that
// jsdom doesn't provide. Stub the whole library so any page using MapView can
// be tested; page tests assert on content, not on the rendered tile canvas.
const leafletStubs = vi.hoisted(() => {
    const NullComponent: () => null = () => null;

    return {
        MapContainer: NullComponent,
        TileLayer: NullComponent,
        CircleMarker: NullComponent,
        Popup: NullComponent,
        useMap: (): { setView: () => void } => ({ setView: () => {} }),
    };
});

vi.mock('react-leaflet', () => leafletStubs);

// Laravel Echo resolves the Pusher client from the global scope or an explicit
// `client` option; jsdom has neither a working WebSocket nor a real connector,
// so a muted stub lets RealtimeProvider (and every hook using
// useRealtimeChannel) construct without touching the network.
const pusherStub = vi.hoisted(() => {
    class PusherChannelStub {
        bind(): void {}
        unbind(): void {}
        trigger(): void {}
        emit(): void {}
    }

    class PusherStub {
        connection = { state: 'connected', bind: () => {}, unbind: () => {} };

        subscribe(): PusherChannelStub {
            return new PusherChannelStub();
        }

        unsubscribe(): void {}
        disconnect(): void {}
        allChannels(): PusherChannelStub[] {
            return [];
        }
    }

    return { PusherStub };
});

vi.mock('pusher-js', () => ({ default: pusherStub.PusherStub }));

Object.assign(globalThis, { Pusher: pusherStub.PusherStub });
Object.assign(globalThis.window ?? {}, { Pusher: pusherStub.PusherStub });