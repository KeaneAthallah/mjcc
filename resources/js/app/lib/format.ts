const numberFormat = new Intl.NumberFormat('id-ID');

export function formatNumber(value: number): string {
    return numberFormat.format(value);
}

const compactFormat = new Intl.NumberFormat('id-ID', {
    notation: 'compact',
    maximumFractionDigits: 1,
});

export function compactNumber(value: number): string {
    return compactFormat.format(value);
}

export function formatDateTime(iso: string | null | undefined): string {
    if (!iso) {
        return 'Belum ada data';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}