import { Chart as ChartJS, registerables } from 'chart.js';

// `registerables` covers every controller/scale/element used across the app
// (bar, stacked bar, doughnut, polar area) with a single, stable registration.
ChartJS.register(...registerables);
ChartJS.defaults.font.family = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif";
ChartJS.defaults.plugins.legend.labels.usePointStyle = true;
ChartJS.defaults.plugins.legend.labels.boxWidth = 8;

type ChartOptions = Record<string, unknown>;

export function baseOptions(): ChartOptions {
    return {
        responsive: true,
        maintainAspectRatio: false,
    };
}

export function stackedBarOptions(): ChartOptions {
    return {
        ...baseOptions(),
        scales: {
            x: { stacked: true },
            y: { stacked: true, beginAtZero: true },
        },
    };
}

export const WORKFORCE_PALETTE = ['#2563eb', '#10b981', '#a7f3d0'];
export const INFRA_PALETTE = ['#10b981', '#3b82f6', '#f87171'];