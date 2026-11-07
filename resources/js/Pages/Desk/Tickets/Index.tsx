import PriorityBadge from '@/Components/Ticketing/PriorityBadge';
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
import { Option, Paginated, Ticket, TicketStatusValue } from '@/types';
import { Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

interface Filters {
    view: '' | 'mine' | 'unassigned';
    status: string;
    team: string;
    search: string;
}

export default function Index({
    tickets,
    filters,
    counts,
    teams,
    statuses,
}: {
    tickets: Paginated<Ticket>;
    filters: Filters;
    counts: { mine: number; unassigned: number };
    teams: Array<{ id: number; name: string }>;
    statuses: Option<TicketStatusValue>[];
}) {
    const [search, setSearch] = useState(filters.search);
    const isFirstRender = useRef(true);

    const applyFilters = (changes: Partial<Filters>) => {
        const query = Object.fromEntries(
            Object.entries({ ...filters, search, ...changes }).filter(([key, value]) => value !== '' && !(key === 'status' && value === 'open')),
        );

        router.get(route('desk.tickets.index'), query, { preserveState: true, preserveScroll: true, replace: true });
    };

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const timeout = setTimeout(() => applyFilters({ search }), 300);

        return () => clearTimeout(timeout);
    }, [search]);

    const tabs: Array<{ value: Filters['view']; label: string; count?: number }> = [
        { value: '', label: 'Todos' },
        { value: 'mine', label: 'Mis tickets', count: counts.mine },
        { value: 'unassigned', label: 'Sin asignar', count: counts.unassigned },
    ];

    return (
        <AppLayout title="Bandeja">
            <PageHeader
                title="Bandeja del equipo"
                description="Los tickets de tus equipos, los que vencen primero arriba."
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
                        {tab.count !== undefined && (
                            <span
                                className={classNames(
                                    'min-w-6 rounded-full px-2 text-xs font-extrabold',
                                    filters.view === tab.value ? 'bg-crimson text-white' : 'bg-wine/10 text-wine',
                                )}
                            >
                                {tab.count}
                            </span>
                        )}
                    </button>
                ))}
            </div>

            <div className="mb-5 flex flex-wrap items-center gap-3">
                <div className="relative min-w-60 flex-1 md:max-w-sm">
                    <label htmlFor="search" className="sr-only">
                        Buscar por asunto o número
                    </label>
                    <Icon name="search" className="pointer-events-none absolute top-3.5 left-4 text-muted" />
                    <TextInput
                        id="search"
                        type="search"
                        value={search}
                        placeholder="Buscar por asunto o #número"
                        className="rounded-full ps-11"
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
                <div className="w-48">
                    <label htmlFor="status" className="sr-only">Filtrar por estado</label>
                    <Select id="status" value={filters.status} onChange={(e) => applyFilters({ status: e.target.value })}>
                        <option value="open">Abiertos</option>
                        <option value="all">Todos los estados</option>
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>{status.label}</option>
                        ))}
                    </Select>
                </div>
                {teams.length > 1 && (
                    <div className="w-60">
                        <label htmlFor="team" className="sr-only">Filtrar por equipo</label>
                        <Select id="team" value={filters.team} onChange={(e) => applyFilters({ team: e.target.value })}>
                            <option value="">Todos mis equipos</option>
                            {teams.map((team) => (
                                <option key={team.id} value={team.id}>{team.name}</option>
                            ))}
                        </Select>
                    </div>
                )}
            </div>

            <div className="glass overflow-x-auto">
                <table className="w-full border-collapse text-left">
                    <caption className="sr-only">Tickets de la bandeja</caption>
                    <thead>
                        <tr className="border-b border-line text-xs tracking-[0.07em] text-muted uppercase">
                            <th scope="col" className="px-3 py-3.5">Ticket</th>
                            <th scope="col" className="hidden px-3 py-3.5 xl:table-cell">Equipo</th>
                            <th scope="col" className="hidden px-3 py-3.5 md:table-cell">Prioridad</th>
                            <th scope="col" className="px-3 py-3.5">Estado</th>
                            <th scope="col" className="px-3 py-3.5">SLA</th>
                            <th scope="col" className="hidden px-3 py-3.5 lg:table-cell">Asignado</th>
                        </tr>
                    </thead>
                    <tbody>
                        {tickets.data.map((ticket) => (
                            <tr key={ticket.id} className="border-b border-line last:border-0 hover:bg-white/55">
                                <td className="px-3 py-3.5">
                                    <Link href={route('desk.tickets.show', ticket.id)} className="group block">
                                        <span className="font-extrabold text-crimson-dark">#{ticket.id}</span>{' '}
                                        <span className="font-extrabold underline decoration-crimson/40 underline-offset-4 group-hover:text-crimson-dark">
                                            {ticket.subject}
                                        </span>
                                        <span className="block text-sm text-muted">
                                            {ticket.requester?.name} · {ticket.category?.name}
                                        </span>
                                    </Link>
                                </td>
                                <td className="hidden px-3 py-3.5 font-bold xl:table-cell">{ticket.team?.name}</td>
                                <td className="hidden px-3 py-3.5 md:table-cell">
                                    <PriorityBadge priority={ticket.priority.value} label={ticket.priority.label} />
                                </td>
                                <td className="px-3 py-3.5"><StatusBadge status={ticket.status} /></td>
                                <td className="px-3 py-3.5">{ticket.sla && <SlaBadge sla={ticket.sla} />}</td>
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
                            </tr>
                        ))}
                    </tbody>
                </table>

                {tickets.data.length === 0 && (
                    <EmptyState icon="inbox" title="Bandeja al día" description="No hay tickets con estos filtros." />
                )}
            </div>

            <Pagination meta={tickets.meta} />
        </AppLayout>
    );
}
