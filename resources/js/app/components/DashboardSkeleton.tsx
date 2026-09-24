export function DashboardSkeleton({ cards = 6 }: { cards?: number }): React.JSX.Element {
    return (
        <div className="space-y-5">
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                {Array.from({ length: cards }).map((_, index) => (
                    <div key={index} className="rounded-xl bg-white border border-gray-200 p-4 h-28 animate-pulse" />
                ))}
            </div>
            <div className="rounded-2xl bg-white border border-gray-200 h-72 animate-pulse" />
        </div>
    );
}