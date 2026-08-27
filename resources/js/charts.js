import Chart from 'chart.js/auto';

export const palette = ['#10b981', '#059669', '#047857', '#3b82f6', '#2563eb', '#1d4ed8', '#6ee7b7', '#34d399', '#a7f3d0', '#60a5fa'];

export const chartGlobals = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                font: { size: 11, family: "'Instrument Sans', sans-serif" },
            },
        },
    },
};

export function makeBar(canvas, labels, datasets, options = {}) {
    return new Chart(canvas, {
        type: 'bar',
        data: { labels, datasets },
        options: { ...chartGlobals, scales: { y: { beginAtZero: true } }, ...options },
    });
}

export function makeStackedBar(canvas, labels, datasets, options = {}) {
    return new Chart(canvas, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            ...chartGlobals,
            scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
            ...options,
        },
    });
}

export function makeHorizontalBar(canvas, labels, datasets, options = {}) {
    return new Chart(canvas, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            ...chartGlobals,
            indexAxis: 'y',
            scales: { x: { beginAtZero: true } },
            ...options,
        },
    });
}

export function makeDoughnut(canvas, labels, data, colors, options = {}) {
    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data, backgroundColor: colors }],
        },
        options: { ...chartGlobals, ...options },
    });
}

export function makePie(canvas, labels, data, colors, options = {}) {
    return new Chart(canvas, {
        type: 'pie',
        data: {
            labels,
            datasets: [{ data, backgroundColor: colors }],
        },
        options: { ...chartGlobals, ...options },
    });
}

export function makePolar(canvas, labels, data, colors, options = {}) {
    return new Chart(canvas, {
        type: 'polarArea',
        data: {
            labels,
            datasets: [{ data, backgroundColor: colors }],
        },
        options: { ...chartGlobals, ...options },
    });
}
