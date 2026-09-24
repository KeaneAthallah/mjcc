const TONES: Record<string, { chip: string; ring: string }> = {
    green: { chip: 'bg-emerald-100 text-emerald-600', ring: 'border-emerald-200' },
    blue: { chip: 'bg-blue-100 text-blue-600', ring: 'border-blue-200' },
    red: { chip: 'bg-red-100 text-red-600', ring: 'border-red-200' },
    amber: { chip: 'bg-amber-100 text-amber-600', ring: 'border-amber-200' },
    violet: { chip: 'bg-violet-100 text-violet-600', ring: 'border-violet-200' },
    teal: { chip: 'bg-teal-100 text-teal-600', ring: 'border-teal-200' },
    emerald: { chip: 'bg-emerald-100 text-emerald-600', ring: 'border-emerald-200' },
};

export function MetricCard({
    label,
    value,
    footer,
    icon,
    tone = 'green',
}: {
    label: string;
    value: string;
    footer?: string;
    icon: string;
    tone?: string;
}): React.JSX.Element {
    const t = TONES[tone] ?? TONES.green;

    return (
        <div className={`rounded-xl border bg-white p-4 shadow-sm ${t.ring}`}>
            <div className="flex items-start justify-between">
                <span className={`w-9 h-9 rounded-xl ${t.chip} flex items-center justify-center text-base`}>{icon}</span>
            </div>
            <div className="mt-2 text-[11px] tracking-widest font-bold text-gray-400 uppercase">{label}</div>
            <div className="text-[24px] font-extrabold text-gray-900 leading-none tabular-nums">{value}</div>
            {footer ? <div className="mt-1.5 text-[11px] text-gray-500">{footer}</div> : null}
        </div>
    );
}