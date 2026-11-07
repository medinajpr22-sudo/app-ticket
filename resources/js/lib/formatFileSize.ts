const megabytes = new Intl.NumberFormat('es', { maximumFractionDigits: 1 });

/**
 * Human friendly file size, e.g. 2,4 MB or 10 MB.
 */
export function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${megabytes.format(bytes / (1024 * 1024))} MB`;
}
