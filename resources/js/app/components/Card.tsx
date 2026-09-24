import type { ReactNode } from 'react';

interface CardProps {
    title?: string;
    subtitle?: string;
    icon?: ReactNode;
    actions?: ReactNode;
    className?: string;
    children: ReactNode;
}

export function Card({ title, subtitle, icon, actions, className = '', children }: CardProps): React.JSX.Element {
    return (
        <div className={`rounded-2xl bg-white border border-gray-200 shadow-sm ${className}`}>
            {title ? (
                <div className="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
                    <div className="flex items-center gap-2.5 min-w-0">
                        {icon ? <span className="text-lg">{icon}</span> : null}
                        <div className="min-w-0">
                            <h2 className="text-[14px] font-extrabold text-gray-800 truncate">{title}</h2>
                            {subtitle ? <p className="text-[11px] text-gray-400 truncate">{subtitle}</p> : null}
                        </div>
                    </div>
                    {actions ? <div className="shrink-0">{actions}</div> : null}
                </div>
            ) : null}
            <div className="p-5">{children}</div>
        </div>
    );
}