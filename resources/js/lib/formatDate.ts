const relativeTime = new Intl.RelativeTimeFormat('es', { numeric: 'auto' });

const units: Array<[Intl.RelativeTimeFormatUnit, number]> = [
    ['year', 60 * 60 * 24 * 365],
    ['month', 60 * 60 * 24 * 30],
    ['week', 60 * 60 * 24 * 7],
    ['day', 60 * 60 * 24],
    ['hour', 60 * 60],
    ['minute', 60],
];

/**
 * Human friendly distance to now in Spanish, e.g. "hace 3 horas".
 */
export function formatRelative(isoDate: string): string {
    const seconds = Math.round((new Date(isoDate).getTime() - Date.now()) / 1000);

    for (const [unit, unitSeconds] of units) {
        if (Math.abs(seconds) >= unitSeconds) {
            return relativeTime.format(Math.round(seconds / unitSeconds), unit);
        }
    }

    return 'hace un momento';
}
