import type { ReactNode } from 'react';

const COLORS: Record<string, string> = {
    green: 'bg-emerald-100 text-emerald-700',
    emerald: 'bg-emerald-100 text-emerald-700',
    red: 'bg-red-100 text-red-700',
    amber: 'bg-amber-100 text-amber-700',
    blue: 'bg-blue-100 text-blue-700',
    violet: 'bg-violet-100 text-violet-700',
    teal: 'bg-teal-100 text-teal-700',
    gray: 'bg-gray-100 text-gray-600',
};

export function Badge({
    color = 'gray',
    children,
}: {
    color?: string;
    children: ReactNode;
}): React.JSX.Element {
    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-bold ${COLORS[color] ?? COLORS.gray}`}>
            {children}
        </span>
    );
}