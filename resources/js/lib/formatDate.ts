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

const dateTime = new Intl.DateTimeFormat('es', { dateStyle: 'medium', timeStyle: 'short' });

const shortDate = new Intl.DateTimeFormat('es', { day: 'numeric', month: 'short', year: 'numeric' });

/**
 * A precise moment in Spanish, e.g. "7 nov 2026, 10:32".
 */
export function formatDateTime(isoDate: string): string {
    return dateTime.format(new Date(isoDate));
}

/**
 * A calendar day written as Y-m-d, in Spanish, e.g. "7 nov 2026".
 * The day is read as local so it never shifts by the time zone.
 */
export function formatDay(day: string): string {
    const [year, month, date] = day.split('-').map(Number);

    return shortDate.format(new Date(year, month - 1, date));
}
