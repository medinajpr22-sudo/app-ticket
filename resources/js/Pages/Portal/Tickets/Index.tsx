import StatusBadge from '@/Components/Ticketing/StatusBadge';
import ButtonLink from '@/Components/ui/ButtonLink';
import EmptyState from '@/Components/ui/EmptyState';
import Icon from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import Pagination from '@/Components/ui/Pagination';
import AppLayout from '@/Layouts/AppLayout';
import { classNames } from '@/lib/classNames';
import { formatRelative } from '@/lib/formatDate';
import { Paginated, Ticket } from '@/types';
import { Link, router } from '@inertiajs/react';

type StatusFilter = '' | 'open' | 'finished' | 'archived';

const statusFilters: Array<{ value: StatusFilter; label: string }> = [
    { value: '', label: 'Todos' },
    { value: 'open', label: 'Abiertos' },
    { value: 'finished', label: 'Finalizados' },
    { value: 'archived', label: 'Archivados' },
];

export default function Index({ tickets, filters }: { tickets: Paginated<Ticket>; filters: { status: StatusFilter } }) {
    const filterBy = (status: StatusFilter) =>
        router.get(route('portal.tickets.index'), status ? { status } : {}, { preserveState: true, replace: true });

    return (
        <AppLayout title="Mis tickets">
            <PageHeader
                title="Mis tickets"
                description="Tus solicitudes a TI y en qué van."
                actions={
                    <ButtonLink href={route('portal.tickets.create')}>
                        <Icon name="plus" />
                        Nuevo ticket
                    </ButtonLink>
                }
            />

            <div role="group" aria-label="Filtrar por estado" className="mb-5 flex flex-wrap gap-2">
                {statusFilters.map((option) => (
                    <button
                        key={option.label}
                        type="button"
                        aria-pressed={filters.status === option.value}
                        onClick={() => filterBy(option.value)}
                        className={classNames(
                            'min-h-11 rounded-full border-[1.5px] px-4 text-sm font-bold transition',
                            filters.status === option.value
                                ? 'border-wine bg-wine text-white'
                                : 'border-field bg-white/85 hover:border-crimson',
                        )}
                    >
                        {option.label}
                    </button>
                ))}
            </div>

            <div className="glass overflow-hidden">
                {tickets.data.length > 0 ? (
                    <ul className="divide-y divide-line">
                        {tickets.data.map((ticket) => (
                            <li key={ticket.id}>
                                <Link
                                    href={route('portal.tickets.show', ticket.id)}
                                    className="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-4 hover:bg-white/55"
                                >
                                    <span className="font-extrabold text-crimson-dark">#{ticket.id}</span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block font-extrabold underline decoration-crimson/40 underline-offset-4">
                                            {ticket.subject}
                                        </span>
                                        <span className="block text-sm text-muted">
                                            {ticket.category?.name} · {ticket.assignee ? `Te atiende ${ticket.assignee.name}` : 'Esperando a que alguien lo tome'}
                                        </span>
                                    </span>
                                    <span className="text-sm font-semibold text-muted">
                                        {formatRelative(ticket.updated_at ?? ticket.created_at)}
                                    </span>
                                    <StatusBadge status={ticket.status} />
                                </Link>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <EmptyState
                        icon="ticket"
                        title={filters.status === 'archived' ? 'No tienes tickets archivados' : filters.status ? 'No hay tickets con este filtro' : 'Todavía no tienes tickets'}
                        description="Cuando necesites ayuda de TI, crea un ticket y te avisaremos de cada avance."
                        action={<ButtonLink href={route('portal.tickets.create')}>Crear un ticket</ButtonLink>}
                    />
                )}
            </div>

            <Pagination meta={tickets.meta} />
        </AppLayout>
    );
}
