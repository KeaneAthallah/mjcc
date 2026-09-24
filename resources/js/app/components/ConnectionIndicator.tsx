import type { ConnectionState } from '../lib/realtime';

const STATES: Record<ConnectionState, { label: string; dot: string; pill: string }> = {
    connected: {
        label: 'LIVE',
        dot: 'bg-emerald-400',
        pill: 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
    },
    connecting: {
        label: 'MENGHUBUNGKAN',
        dot: 'bg-amber-400 animate-pulse',
        pill: 'bg-amber-500/15 text-amber-300 border-amber-500/30',
    },
    disconnected: {
        label: 'TERPUTUS',
        dot: 'bg-red-400',
        pill: 'bg-red-500/15 text-red-300 border-red-500/30',
    },
    unknown: {
        label: 'OFFLINE',
        dot: 'bg-gray-400',
        pill: 'bg-white/10 text-white/60 border-white/10',
    },
};

export function ConnectionIndicator({ state }: { state: ConnectionState }): React.JSX.Element {
    const shown = STATES[state] ?? STATES.unknown;

    return (
        <span
            title="Status koneksi realtime"
            className={`inline-flex items-center gap-1.5 border rounded-full px-2.5 py-1 text-[10px] font-bold tracking-widest uppercase ${shown.pill}`}
        >
            <span className={`w-1.5 h-1.5 rounded-full ${shown.dot}`} />
            {shown.label}
        </span>
    );
}