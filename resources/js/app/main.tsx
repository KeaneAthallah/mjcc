import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { RouterProvider } from 'react-router-dom';
import { RealtimeProvider } from './lib/realtime';
import { router } from './router';

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            refetchOnWindowFocus: false,
            retry: 1,
        },
    },
});

const container = document.getElementById('app');

if (!container) {
    throw new Error('Mount point #app tidak ditemukan. Pastikan shell Blade render elemen #app.');
}

// RealtimeProvider owns a long-lived WebSocket; StrictMode's dev double-mount
// would disconnect it before the handshake completes (hence the
// "closed before the connection is established" error), so it is not wrapped.
createRoot(container).render(
    <QueryClientProvider client={queryClient}>
        <RealtimeProvider>
            <RouterProvider router={router} />
        </RealtimeProvider>
    </QueryClientProvider>,
);