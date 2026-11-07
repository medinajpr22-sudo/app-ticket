import { classNames } from '@/lib/classNames';
import { ReactNode } from 'react';

/**
 * One headline number with what it means. The first tile of a row can be
 * highlighted; the rest stay quiet so the numbers do the talking.
 */
export default function MetricTile({
    label,
    value,
    hint,
    highlighted = false,
}: {
    label: string;
    value: ReactNode;
    hint?: ReactNode;
    highlighted?: boolean;
}) {
    return (
        <div
            className={classNames(
                'h-full rounded-2xl px-5 py-4',
                highlighted
                    ? 'border border-white/30 bg-wine/92 text-white shadow-[0_12px_40px_rgb(86_19_23/0.25)]'
                    : 'glass',
            )}
        >
            <dt className={classNames('text-sm font-bold', highlighted ? 'text-blush' : 'text-muted')}>{label}</dt>
            <dd className="mt-0.5 text-3xl font-extrabold tracking-tight tabular-nums">{value}</dd>
            {hint && (
                <dd className={classNames('mt-1 text-sm font-semibold', highlighted ? 'text-blush' : 'text-muted')}>{hint}</dd>
            )}
        </div>
    );
}
