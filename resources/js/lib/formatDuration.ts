/**
 * Formats business minutes as hours and minutes, e.g. 90 becomes "1 h 30 min".
 * Days are not used because SLA time only counts working hours.
 */
export function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const remainder = minutes % 60;

    if (hours === 0) {
        return `${remainder} min`;
    }

    return remainder === 0 ? `${hours} h` : `${hours} h ${remainder} min`;
}
