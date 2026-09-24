import type { ReactNode } from 'react';

interface ChartCardProps {
    title: string;
    subtitle?: string;
    icon?: string;
    className?: string;
    children: ReactNode;
}

export function ChartCard({ title, subtitle, icon, className = '', children }: ChartCardProps): React.JSX.Element {
    return (
        <div className={`rounded-2xl bg-white border border-gray-200 shadow-sm ${className}`}>
            <div className="flex items-center gap-2.5 px-5 py-4 border-b border-gray-100">
                {icon ? <span className="text-lg">{icon}</span> : null}
                <div className="min-w-0">
                    <h2 className="text-[14px] font-extrabold text-gray-800 truncate">{title}</h2>
                    {subtitle ? <p className="text-[11px] text-gray-400 truncate">{subtitle}</p> : null}
                </div>
            </div>
            <div className="h-72 p-4">
                <div className="h-full">{children}</div>
            </div>
        </div>
    );
}