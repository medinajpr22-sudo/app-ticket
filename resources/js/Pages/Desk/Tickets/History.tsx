import PeriodFields from '@/Components/Reports/PeriodFields';
import SlaBadge from '@/Components/Ticketing/SlaBadge';
import StatusBadge from '@/Components/Ticketing/StatusBadge';
import Avatar from '@/Components/ui/Avatar';
import EmptyState from '@/Components/ui/EmptyState';
import Icon from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import Pagination from '@/Components/ui/Pagination';
import Select from '@/Components/ui/Select';
import TextInput from '@/Components/ui/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { classNames } from '@/lib/classNames';
import { formatDateTime } from '@/lib/formatDate';
import { Option, PageProps, Paginated, Ticket, TicketStatusValue } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

interface Filters {
    view: '' | 'mine';
    status: '' | TicketStatusValue;
    team: string;
    search: string;
    from: string;
    to: string;
}

/**
 * What the team already finished: resolved tickets waiting to be closed and
 * closed ones, the most recent first.
 */
export default function History({
    tickets,
    filters,
    counts,
    teams,
    statuses,
}: {
    tickets: Paginated<Ticket>;
    filters: Filters;
    counts: { all: number; mine: number };
    teams: Array<{ id: number; name: string }>;
    statuses: Option<TicketStatusValue>[];
}) {
    const { errors } = usePage<PageProps & { errors: Record<string, string> }>().props;
    const [search, setSearch] = useState(filters.search);
    const isFirstRender = useRef(true);

    const applyFilters = (changes: Partial<Filters>) => {
        const query = Object.fromEntries(
            Object.entries({ ...filters, search, ...changes }).filter(([, value]) => value !== ''),
        );

        router.get(route('desk.history.index'), query, { preserveState: true, preserveScroll: true, replace: true });
    };

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const timeout = setTimeout(() => applyFilters({ search }), 300);

        return () => clearTimeout(timeout);
    }, [search]);

    const tabs: Array<{ value: Filters['view']; label: string; count: number }> = [
        { value: '', label: 'Todos', count: counts.all },
        { value: 'mine', label: 'Mis tickets', count: counts.mine },
    ];

    return (
        <AppLayout title="Historial">
            <PageHeader
                title="Historial de tickets"
                description="Los tickets que tus equipos resolvieron o cerraron, los más recientes primero."
            />

            <div role="group" aria-label="Vista" className="mb-4 flex flex-wrap gap-2">
                {tabs.map((tab) => (
                    <button
                        key={tab.label}
                        type="button"
                        aria-pressed={filters.view === tab.value}
                        onClick={() => applyFilters({ view: tab.value })}
                        className={classNames(
                            'inline-flex min-h-11 items-center gap-2 rounded-full border-[1.5px] px-4 text-sm font-bold transition',
                            filters.view === tab.value ? 'border-wine bg-wine text-white' : 'border-field bg-white/85 hover:border-crimson',
                        )}
                    >
                        {tab.label}
                        <span
                            className={classNames(
                                'min-w-6 rounded-full px-2 text-xs font-extrabold',
                                filters.view === tab.value ? 'bg-crimson text-white' : 'bg-wine/10 text-wine',
                            )}
                        >
                            {tab.count}
                        </span>
                    </button>
                ))}
            </div>

            <div className="mb-5 flex flex-wrap items-end gap-3">
                <div className="relative min-w-60 flex-1 md:max-w-sm">
                    <label htmlFor="search" className="mb-1 block text-sm font-extrabold">Buscar</label>
                    <Icon name="search" className="pointer-events-none absolute bottom-3.5 left-4 text-muted" />
                    <TextInput
                        id="search"
                        type="search"
                        value={search}
                        placeholder="Asunto o #número"
                        className="rounded-full ps-11"
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
                <div className="w-52">
                    <label htmlFor="status" className="mb-1 block text-sm font-extrabold">Estado</label>
                    <Select id="status" value={filters.status} onChange={(e) => applyFilters({ status: e.target.value as Filters['status'] })}>
                        <option value="">Resueltos y cerrados</option>
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>{status.label}</option>
                        ))}
                    </Select>
                </div>
                {teams.length > 0 && (
                    <div className="w-60">
                        <label htmlFor="team" className="mb-1 block text-sm font-extrabold">Equipo</label>
                        <Select id="team" value={filters.team} onChange={(e) => applyFilters({ team: e.target.value })}>
                            <option value="">Todos mis equipos</option>
                            {teams.map((team) => (
                                <option key={team.id} value={team.id}>{team.name}</option>
                            ))}
                        </Select>
                    </div>
                )}
                <PeriodFields
                    period={{ from: filters.from, to: filters.to }}
                    onChange={(period) => applyFilters(period)}
                    error={errors.from ?? errors.to}
                />
                {(filters.from || filters.to) && (
                    <button
                        type="button"
                        onClick={() => applyFilters({ from: '', to: '' })}
                        className="min-h-12 px-2 text-sm font-bold text-wine underline-offset-2 hover:underline"
                    >
                        Ver todas las fechas
                    </button>
                )}
            </div>

            <div className="glass overflow-x-auto">
                <table className="w-full border-collapse text-left">
                    <caption className="sr-only">Historial de tickets resueltos y cerrados</caption>
                    <thead>
                        <tr className="border-b border-line text-xs tracking-[0.07em] text-muted uppercase">
                            <th scope="col" className="px-3 py-3.5">Ticket</th>
                            <th scope="col" className="hidden px-3 py-3.5 lg:table-cell">Atendió</th>
                            <th scope="col" className="px-3 py-3.5">Resuelto</th>
                            <th scope="col" className="px-3 py-3.5">Estado</th>
                            <th scope="col" className="hidden px-3 py-3.5 md:table-cell">SLA</th>
                            <th scope="col" className="hidden px-3 py-3.5 md:table-cell">Calificación</th>
                        </tr>
                    </thead>
                    <tbody>
                        {tickets.data.map((ticket) => {
                            const finishedAt = ticket.resolved_at ?? ticket.closed_at;

                            return (
                                <tr key={ticket.id} className="border-b border-line last:border-0 hover:bg-white/55">
                                    <td className="px-3 py-3.5">
                                        <Link href={route('desk.tickets.show', ticket.id)} className="group block">
                                            <span className="font-extrabold text-crimson-dark">#{ticket.id}</span>{' '}
                                            <span className="font-extrabold underline decoration-crimson/40 underline-offset-4 group-hover:text-crimson-dark">
                                                {ticket.subject}
                                            </span>
                                            <span className="block text-sm text-muted">
                                                {ticket.requester?.name} · {ticket.category?.name} · {ticket.team?.name}
                                            </span>
                                        </Link>
                                    </td>
                                    <td className="hidden px-3 py-3.5 lg:table-cell">
                                        {ticket.assignee ? (
                                            <span className="flex items-center gap-2 font-bold">
                                                <Avatar initials={ticket.assignee.initials} size="sm" />
                                                {ticket.assignee.name}
                                            </span>
                                        ) : (
                                            <span className="font-semibold text-muted">Sin asignar</span>
                                        )}
                                    </td>
                                    <td className="px-3 py-3.5 text-sm font-semibold whitespace-nowrap">
                                        {finishedAt ? formatDateTime(finishedAt) : '—'}
                                    </td>
                                    <td className="px-3 py-3.5">
                                        <StatusBadge status={ticket.status} />
                                        {ticket.closed_by && (
                                            <span className="mt-1 block text-xs text-muted">Lo cerró {ticket.closed_by.name}</span>
                                        )}
                                    </td>
                                    <td className="hidden px-3 py-3.5 md:table-cell">{ticket.sla && <SlaBadge sla={ticket.sla} />}</td>
                                    <td className="hidden px-3 py-3.5 text-sm font-bold md:table-cell">
                                        {ticket.rating ? (
                                            <span className="inline-flex items-center gap-1">
                                                <Icon name="star" size={16} className="text-warning" />
                                                {ticket.rating.score} / 5
                                            </span>
                                        ) : (
                                            <span className="font-semibold text-muted">Sin calificar</span>
                                        )}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>

                {tickets.data.length === 0 && (
                    <EmptyState
                        icon="check"
                        title="Todavía no hay tickets terminados"
                        description="Cuando tus equipos resuelvan o cierren tickets, aparecerán aquí."
                    />
                )}
            </div>

            <Pagination meta={tickets.meta} />
        </AppLayout>
    );
}
