import Card from '@/Components/ui/Card';
import { TicketBreakdown } from '@/types';

const share = new Intl.NumberFormat('es', { style: 'percent', maximumFractionDigits: 0 });

/**
 * How many tickets each group got, largest first, as horizontal bars on one
 * baseline so close values are easy to tell apart. Every bar has its number
 * at the tip; hovering or focusing it adds its share of the total.
 */
export default function RankedBars({
    id,
    title,
    description,
    items,
}: {
    id: string;
    title: string;
    description?: string;
    items: TicketBreakdown[];
}) {
    const total = items.reduce((sum, item) => sum + item.total, 0);
    const largest = Math.max(1, ...items.map((item) => item.total));

    return (
        <Card aria-labelledby={`${id}-heading`} className="h-full">
            <h2 id={`${id}-heading`} className="text-lg font-extrabold">{title}</h2>
            {description && <p className="text-sm text-muted">{description}</p>}

            {items.length === 0 ? (
                <p className="mt-4 text-sm font-semibold text-muted">No hay tickets con estos filtros.</p>
            ) : (
                <ol className="mt-4 flex flex-col gap-3">
                    {items.map((item) => {
                        const summary = `${item.label}: ${item.total} ${item.total === 1 ? 'ticket' : 'tickets'}, ${share.format(item.total / total)} del total`;

                        return (
                            <li
                                key={item.id ?? 'none'}
                                tabIndex={0}
                                aria-label={summary}
                                className="group relative rounded-lg outline-offset-4"
                            >
                                <div aria-hidden="true" className="mb-1 flex justify-between gap-3 text-sm">
                                    <span className="truncate font-bold">{item.label}</span>
                                </div>
                                <div aria-hidden="true" className="flex items-center gap-2">
                                    <span className="h-3 min-w-0 flex-1">
                                        <span
                                            className="block h-3 rounded-r-[4px] bg-wine transition-opacity group-hover:opacity-85"
                                            style={{ width: `${Math.max(2, (item.total / largest) * 100)}%` }}
                                        />
                                    </span>
                                    <span className="w-10 shrink-0 text-right text-sm font-extrabold tabular-nums">{item.total}</span>
                                </div>
                                <span
                                    role="tooltip"
                                    className="pointer-events-none absolute -top-2 right-12 z-10 hidden -translate-y-full rounded-lg bg-ink px-2.5 py-1.5 text-xs whitespace-nowrap text-white shadow-lg group-hover:block group-focus-visible:block"
                                >
                                    <strong>{item.total}</strong> · {share.format(item.total / total)} del total
                                </span>
                            </li>
                        );
                    })}
                </ol>
            )}
        </Card>
    );
}
