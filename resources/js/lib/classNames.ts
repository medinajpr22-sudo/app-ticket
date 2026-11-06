/**
 * Joins the truthy class names, so conditional classes read naturally:
 * classNames('btn', isActive && 'btn-active').
 */
export function classNames(
    ...classes: Array<string | false | null | undefined>
): string {
    return classes.filter(Boolean).join(' ');
}
