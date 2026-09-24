import { describe, expect, it } from 'vitest';
import { compactNumber, formatDateTime, formatNumber } from './format';

describe('formatNumber', () => {
    it('formats with Indonesian thousand separators', () => {
        expect(formatNumber(1234567)).toBe('1.234.567');
    });

    it('keeps zero', () => {
        expect(formatNumber(0)).toBe('0');
    });
});

describe('compactNumber', () => {
    it('compacts large numbers', () => {
        expect(compactNumber(1200000)).toBe(`1,2${'\u00a0'}jt`);
    });
});

describe('formatDateTime', () => {
    it('renders a friendly placeholder for missing data', () => {
        expect(formatDateTime(null)).toBe('Belum ada data');
        expect(formatDateTime(undefined)).toBe('Belum ada data');
    });
});