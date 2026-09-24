/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_REVERB_APP_KEY?: string;
    readonly VITE_REVERB_HOST?: string;
    readonly VITE_REVERB_PORT?: string;
    readonly VITE_REVERB_SCHEME?: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}

interface Window {
    Echo?: Echo;
    Pusher?: typeof import('pusher-js').default;
}