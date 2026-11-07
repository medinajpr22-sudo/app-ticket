import { formatDuration } from './formatDuration';

const NOTHING_TO_MEASURE = '—';

const percent = new Intl.NumberFormat('es', { maximumFractionDigits: 1 });

const rating = new Intl.NumberFormat('es', { minimumFractionDigits: 1, maximumFractionDigits: 1 });

/**
 * A share such as SLA compliance, e.g. "92,5 %". A dash when nothing was measured.
 */
export function formatPercent(value: number | null): string {
    return value === null ? NOTHING_TO_MEASURE : `${percent.format(value)} %`;
}

/**
 * Business minutes as hours and minutes, or a dash.
 */
export function formatMinutes(value: number | null): string {
    return value === null ? NOTHING_TO_MEASURE : formatDuration(value);
}

/**
 * An average rating out of five, e.g. "4,5 / 5", or a dash.
 */
export function formatRating(value: number | null): string {
    return value === null ? NOTHING_TO_MEASURE : `${rating.format(value)} / 5`;
}
