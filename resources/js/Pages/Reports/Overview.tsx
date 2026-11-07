import MetricTile from '@/Components/Reports/MetricTile';
import PerformanceTable from '@/Components/Reports/PerformanceTable';
import PeriodFields from '@/Components/Reports/PeriodFields';
import RankedBars from '@/Components/Reports/RankedBars';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import Select from '@/Components/ui/Select';
import AppLayout from '@/Layouts/AppLayout';
import { formatMinutes, formatPercent, formatRating } from '@/lib/formatMetric';
import { Option, PageProps, ReportPeriod, TicketBreakdown, TicketMetrics, TicketStatusValue } from '@/types';
import { router, usePage } from '@inertiajs/react';

interface Filters extends ReportPeriod {
    department: string;
    team: string;
    status: string;
}

interface NamedMetrics {
    id: number;
    name: string;
    metrics: TicketMetrics;
}

interface AgentMetrics extends NamedMetrics {
    initials: string;
    teams: string[];
    open_now: number;
}

export default function Overview({
    filters,
    summary,
    byDepartment,
    byCategory,
    byTeam,
    teamPerformance,
    agentPerformance,
    options,
    platform,
}: {
    filters: Filters;
    summary: TicketMetrics;
    byDepartment: TicketBreakdown[];
    byCategory: TicketBreakdown[];
    byTeam: TicketBreakdown[];
    teamPerformance: NamedMetrics[];
    agentPerformance: AgentMetrics[];
    options: {
        departments: Array<{ id: number; name: string }>;
        teams: Array<{ id: number; name: string }>;
        statuses: Option<TicketStatusValue>[];
    };
    platform: { open_tickets: number; active_users: number; staff: number; teams: number };
}) {
    const { errors } = usePage<PageProps & { errors: Record<string, string> }>().props;

    const applyFilters = (changes: Partial<Filters>) => {
        const query = Object.fromEntries(Object.entries({ ...filters, ...changes }).filter(([, value]) => value !== ''));

        router.get(route('reports.overview'), query, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <AppLayout title="Estadísticas">
            <PageHeader
                title="Estadísticas de la mesa de ayuda"
                description="Cuántos soportes llegan, de dónde, y cómo los atiende cada equipo y cada agente."
            />

            <div role="search" aria-label="Filtros de las estadísticas" className="mb-6 flex flex-wrap items-end gap-3">
                <PeriodFields
                    period={{ from: filters.from, to: filters.to }}
                    onChange={(period) => applyFilters(period)}
                    error={errors.from ?? errors.to}
                />
                <div className="w-52">
                    <label htmlFor="department" className="mb-1 block text-sm font-extrabold">Área</label>
                    <Select id="department" value={filters.department} onChange={(e) => applyFilters({ department: e.target.value })}>
                        <option value="">Todas las áreas</option>
                        {options.departments.map((department) => (
                            <option key={department.id} value={department.id}>{department.name}</option>
                        ))}
                    </Select>
                </div>
                <div className="w-52">
                    <label htmlFor="team" className="mb-1 block text-sm font-extrabold">Equipo</label>
                    <Select id="team" value={filters.team} onChange={(e) => applyFilters({ team: e.target.value })}>
                        <option value="">Todos los equipos</option>
                        {options.teams.map((team) => (
                            <option key={team.id} value={team.id}>{team.name}</option>
                        ))}
                    </Select>
                </div>
                <div className="w-52">
                    <label htmlFor="status" className="mb-1 block text-sm font-extrabold">Estado</label>
                    <Select id="status" value={filters.status} onChange={(e) => applyFilters({ status: e.target.value })}>
                        <option value="">Todos los estados</option>
                        <option value="open">Abiertos</option>
                        {options.statuses.map((status) => (
                            <option key={status.value} value={status.value}>{status.label}</option>
                        ))}
                    </Select>
                </div>
            </div>

            <h2 className="sr-only">Resumen</h2>
            <dl className="grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-4">
                <MetricTile highlighted label="Tickets recibidos" value={summary.total} hint={`${summary.open} siguen abiertos`} />
                <MetricTile label="Recibido (sin tomar)" value={summary.received} />
                <MetricTile label="En proceso" value={summary.in_progress} />
                <MetricTile label="Esperando al usuario" value={summary.waiting} />
                <MetricTile label="Resueltos" value={summary.resolved} />
            </dl>
            <dl className="mt-4 grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-4">
                <MetricTile label="SLA cumplido" value={formatPercent(summary.resolution_compliance)} hint="Resueltos a tiempo" />
                <MetricTile label="Primera respuesta a tiempo" value={formatPercent(summary.first_response_compliance)} />
                <MetricTile label="Primera respuesta promedio" value={formatMinutes(summary.average_first_response_minutes)} hint="En horario laboral" />
                <MetricTile label="Resolución promedio" value={formatMinutes(summary.average_resolution_minutes)} hint="En horario laboral" />
                <MetricTile
                    label="Calificación promedio"
                    value={formatRating(summary.average_rating)}
                    hint={`${summary.ratings} ${summary.ratings === 1 ? 'calificación' : 'calificaciones'}`}
                />
            </dl>

            <div className="mt-5 grid gap-5 xl:grid-cols-3">
                <RankedBars id="by-department" title="Por área" description="El área de quien pide el soporte." items={byDepartment} />
                <RankedBars id="by-category" title="Por categoría" description="Las 10 más solicitadas." items={byCategory} />
                <RankedBars id="by-team" title="Por equipo" description="El equipo que los atiende." items={byTeam} />
            </div>

            <Card aria-labelledby="teams-heading" className="mt-5 p-0">
                <div className="px-6 pt-6 pb-2">
                    <h2 id="teams-heading" className="text-xl font-extrabold">Rendimiento por equipo</h2>
                </div>
                <PerformanceTable
                    caption="Rendimiento por equipo"
                    nameHeader="Equipo"
                    emptyText="No hay tickets con estos filtros."
                    rows={teamPerformance.map((team) => ({
                        key: team.id,
                        name: team.name,
                        href: route('desk.teams.show', { team: team.id, from: filters.from, to: filters.to }),
                        metrics: team.metrics,
                    }))}
                />
            </Card>

            <Card aria-labelledby="agents-heading" className="mt-5 p-0">
                <div className="px-6 pt-6 pb-2">
                    <h2 id="agents-heading" className="text-xl font-extrabold">Rendimiento por agente</h2>
                    <p className="text-sm text-muted">«Abiertos ahora» es la carga de hoy de cada agente, sin importar el periodo.</p>
                </div>
                <PerformanceTable
                    caption="Rendimiento por agente"
                    nameHeader="Agente"
                    showOpenNow
                    emptyText="Ningún agente tiene tickets con estos filtros."
                    rows={agentPerformance.map((agent) => ({
                        key: agent.id,
                        name: agent.name,
                        initials: agent.initials,
                        details: agent.teams.join(' · '),
                        openNow: agent.open_now,
                        metrics: agent.metrics,
                    }))}
                />
            </Card>

            <p className="mt-3 text-sm font-semibold text-muted">
                El periodo cuenta los tickets creados en esas fechas. Hoy hay {platform.open_tickets} tickets abiertos en total ·{' '}
                {platform.active_users} cuentas activas · {platform.staff} personas de TI · {platform.teams} equipos.
            </p>
        </AppLayout>
    );
}
