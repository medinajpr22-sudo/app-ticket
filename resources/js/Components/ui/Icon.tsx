import { SVGAttributes } from 'react';

/**
 * Stroke icons from the approved prototype. The keys used by teams and
 * categories must match App\Domain\Shared\Enums\Icon.
 */
const paths = {
    home: 'M3 11l9-8 9 8M5 10v10h14V10',
    plus: 'M12 5v14M5 12h14',
    ticket: 'M4 7h16v3a2 2 0 0 0 0 4v3H4v-3a2 2 0 0 0 0-4zM14 7v10',
    book: 'M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2zM4 19V5M9 7h6',
    inbox: 'M3 13l3-8h12l3 8v6H3zM3 13h5l1 3h6l1-3h5',
    chart: 'M4 20V10M10 20V4M16 20v-8M22 20H2',
    bell: 'M6 16v-5a6 6 0 0 1 12 0v5l2 2H4zM10 21h4',
    laptop: 'M5 5h14v10H5zM2 19h20',
    key: 'M14 10a4 4 0 1 0-3 3.9L13 16h2v2h2v2h3v-3z',
    puzzle: 'M5 4h5v2a2 2 0 1 0 4 0V4h5v5h-2a2 2 0 1 0 0 4h2v7H5v-5h2a2 2 0 1 0 0-4H5z',
    wifi: 'M2 9a15 15 0 0 1 20 0M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0M12 19.5h.01',
    mail: 'M3 6h18v12H3zM3 7l9 7 9-7',
    help: 'M9.5 9a2.5 2.5 0 1 1 4 2c-1 .7-1.5 1.2-1.5 2.5M12 17.5h.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z',
    printer: 'M7 9V3h10v6M7 17H4v-7h16v7h-3M7 14h10v7H7z',
    video: 'M3 6h12v12H3zM15 10l6-3v10l-6-3',
    check: 'M5 12l4 4 10-10',
    search: 'M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14zM21 21l-5-5',
    back: 'M15 6l-6 6 6 6',
    forward: 'M9 6l6 6-6 6',
    clock: 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 7v5l3 2',
    warn: 'M12 4l9 16H3zM12 10v4M12 17h.01',
    lock: 'M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3',
    x: 'M6 6l12 12M18 6L6 18',
    pulse: 'M3 12h4l3-7 4 14 3-7h4',
    drop: 'M12 3c4 5 6 8 6 11a6 6 0 0 1-12 0c0-3 2-6 6-11z',
    grid: 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
    spark: 'M12 3l2 6 6 2-6 2-2 6-2-6-6-2 6-2z',
    server: 'M4 4h16v6H4zM4 14h16v6H4zM8 7h.01M8 17h.01',
    users: 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM3 20a6 6 0 0 1 12 0M17 11a3 3 0 1 0-1-5.8M21 20a5 5 0 0 0-4-4.9',
    shield: 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z',
    tag: 'M3 12V4h8l10 10-8 8zM7.5 7.5h.01',
    pencil: 'M4 20h4L19 9l-4-4L4 16zM14 6l4 4',
    menu: 'M4 6h16M4 12h16M4 18h16',
    logout: 'M9 4H5v16h4M16 8l4 4-4 4M20 12H9',
    user: 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0',
    chevronDown: 'M6 9l6 6 6-6',
    building: 'M4 21V5l8-3 8 3v16M9 9h1M14 9h1M9 13h1M14 13h1M10 21v-4h4v4',
    mapPin: 'M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11zM12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
    star: 'M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z',
    clip: 'M20 11l-8 8a5 5 0 0 1-7-7l8-8a3 3 0 0 1 4 4l-8 8a1 1 0 0 1-2-2l7-7',
    transfer: 'M4 8h13l-3-3M20 16H7l3 3',
    up: 'M12 19V5M6 11l6-6 6 6',
    flat: 'M5 12h14',
    down: 'M12 5v14M6 13l6 6 6-6',
    calendar: 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4',
    globe: 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM3 12h18M12 3c2.5 2.5 3.5 5.5 3.5 9s-1 6.5-3.5 9c-2.5-2.5-3.5-5.5-3.5-9s1-6.5 3.5-9z',
    eyeOff: 'M3 3l18 18M10.6 6.1A9.8 9.8 0 0 1 12 6c5 0 9 6 9 6a17 17 0 0 1-2.6 3.3M6.6 6.6C4.3 8.2 3 12 3 12s4 6 9 6a9 9 0 0 0 4.4-1.2M9.9 9.9a3 3 0 0 0 4.2 4.2',
} as const;

export type IconName = keyof typeof paths;

export default function Icon({
    name,
    size = 20,
    className = '',
    ...props
}: SVGAttributes<SVGSVGElement> & { name: IconName; size?: number }) {
    return (
        <svg
            {...props}
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.9}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            focusable="false"
            className={'shrink-0 ' + className}
        >
            <path d={paths[name]} />
        </svg>
    );
}
