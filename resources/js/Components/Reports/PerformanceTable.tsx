import Avatar from '@/Components/ui/Avatar';
import { formatMinutes, formatPercent, formatRating } from '@/lib/formatMetric';
import { TicketMetrics } from '@/types';
import { Link } from '@inertiajs/react';
import { ReactNode } from 'react';

export interface PerformanceRow {
    key: number;
    name: string;
    initials?: string;
    details?: ReactNode;
    href?: string;
    openNow?: number;
    metrics: TicketMetrics;
}

/**
 * Side-by-side performance: how many tickets each one attended and solved,
 * the SLA kept, how fast they answer and solve, and how they are rated.
 * Solved tickets also get a bar so the comparison reads at a glance.
 */
export default function PerformanceTable({
    caption,
    nameHeader,
    rows,
    showOpenNow = false,
    emptyText,
}: {
    caption: string;
    nameHeader: string;
    rows: PerformanceRow[];
    showOpenNow?: boolean;
    emptyText: string;
}) {
    const mostResolved = Math.max(1, ...rows.map((row) => row.metrics.resolved));

    if (rows.length === 0) {
        return <p className="px-5 py-8 text-center text-sm font-semibold text-muted">{emptyText}</p>;
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[860px] border-collapse text-left text-sm">
                <caption className="sr-only">{caption}</caption>
                <thead>
                    <tr className="border-b border-line text-xs tracking-[0.07em] text-muted uppercase">
                        <th scope="col" className="px-3 py-3">{nameHeader}</th>
                        <th scope="col" className="px-3 py-3 text-right">Atendidos</th>
                        <th scope="col" className="px-3 py-3">Resueltos</th>
                        {showOpenNow && <th scope="col" className="px-3 py-3 text-right">Abiertos ahora</th>}
                        <th scope="col" className="px-3 py-3 text-right">SLA cumplido</th>
                        <th scope="col" className="px-3 py-3 text-right">1.ª respuesta</th>
                        <th scope="col" className="px-3 py-3 text-right">Resolución</th>
                        <th scope="col" className="px-3 py-3 text-right">Calificación</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr key={row.key} className="border-b border-line last:border-0 hover:bg-white/55">
                            <th scope="row" className="px-3 py-3 font-normal">
                                <span className="flex items-center gap-2.5">
                                    {row.initials && <Avatar initials={row.initials} size="sm" />}
                                    <span className="min-w-0">
                                        {row.href ? (
                                            <Link href={row.href} className="font-extrabold underline decoration-crimson/40 underline-offset-4 hover:text-crimson-dark">
                                                {row.name}
                                            </Link>
                                        ) : (
                                            <span className="font-extrabold">{row.name}</span>
                                        )}
                                        {row.details && <span className="block text-xs text-muted">{row.details}</span>}
                                    </span>
                                </span>
                            </th>
                            <td className="px-3 py-3 text-right font-bold tabular-nums">{row.metrics.total}</td>
                            <td className="px-3 py-3">
                                <span className="flex items-center gap-2">
                                    <span aria-hidden="true" className="h-2.5 w-20 shrink-0">
                                        <span
                                            className="block h-2.5 rounded-r-[4px] bg-wine"
                                            style={{ width: `${(row.metrics.resolved / mostResolved) * 100}%` }}
                                        />
                                    </span>
                                    <span className="font-bold tabular-nums">{row.metrics.resolved}</span>
                                </span>
                            </td>
                            {showOpenNow && <td className="px-3 py-3 text-right font-bold tabular-nums">{row.openNow ?? 0}</td>}
                            <td className="px-3 py-3 text-right font-bold tabular-nums">{formatPercent(row.metrics.resolution_compliance)}</td>
                            <td className="px-3 py-3 text-right tabular-nums">{formatMinutes(row.metrics.average_first_response_minutes)}</td>
                            <td className="px-3 py-3 text-right tabular-nums">{formatMinutes(row.metrics.average_resolution_minutes)}</td>
                            <td className="px-3 py-3 text-right tabular-nums">
                                {formatRating(row.metrics.average_rating)}
                                {row.metrics.ratings > 0 && (
                                    <span className="block text-xs text-muted">
                                        {row.metrics.ratings} {row.metrics.ratings === 1 ? 'calificación' : 'calificaciones'}
                                    </span>
                                )}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
