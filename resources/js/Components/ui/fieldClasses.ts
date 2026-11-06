const baseFieldClasses =
    'block min-h-12 rounded-xl border-[1.5px] border-field bg-white/90 px-3.5 py-2.5 text-ink placeholder:text-muted/70 focus:border-focus focus:ring-0 aria-[invalid=true]:border-2 aria-[invalid=true]:border-crimson-dark';

/**
 * Shared look for text inputs, selects and text areas. Fields take the full
 * width unless the caller sets its own width class (e.g. "w-36").
 */
export function fieldClasses(className = ''): string {
    const width = /(^|\s)(w-|max-w-)/.test(className) ? '' : 'w-full';

    return `${baseFieldClasses} ${width} ${className}`;
}
