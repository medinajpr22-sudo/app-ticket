import MetricTile from '@/Components/Reports/MetricTile';
import PerformanceTable from '@/Components/Reports/PerformanceTable';
import PeriodFields from '@/Components/Reports/PeriodFields';
import Badge from '@/Components/ui/Badge';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import Icon, { IconName } from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import Select from '@/Components/ui/Select';
import AppLayout from '@/Layouts/AppLayout';
import { formatMinutes, formatPercent, formatRating } from '@/lib/formatMetric';
import { PageProps, ReportPeriod, TicketMetrics } from '@/types';
import { router, usePage } from '@inertiajs/react';

interface TeamMemberPerformance {
    id: number;
    name: string;
    initials: string;
    email: string;
    job_title: string | null;
    is_lead: boolean;
    is_active: boolean;
    last_login_at: string | null;
    open_now: number;
    metrics: TicketMetrics;
}

export default function Show({
    team,
    ledTeams,
    filters,
    summary,
    unassigned,
    members,
}: {
    team: { id: number; name: string; icon: IconName };
    ledTeams: Array<{ id: number; name: string }>;
    filters: ReportPeriod;
    summary: TicketMetrics;
    unassigned: number;
    members: TeamMemberPerformance[];
}) {
    const { errors } = usePage<PageProps & { errors: Record<string, string> }>().props;

    const changePeriod = (period: ReportPeriod) =>
        router.get(route('desk.teams.show', team.id), { ...period }, { preserveState: true, preserveScroll: true, replace: true });

    return (
        <AppLayout title={`Mi equipo · ${team.name}`}>
            <PageHeader
                title={`Mi equipo · ${team.name}`}
                description="Cómo van tu equipo y cada auxiliar en el periodo elegido."
                actions={
                    <ButtonLink href={route('desk.teams.members.create', team.id)}>
                        <Icon name="plus" />
                        Nuevo auxiliar
                    </ButtonLink>
                }
            />

            <div className="mb-5 flex flex-wrap items-end gap-4">
                {ledTeams.length > 1 && (
                    <div className="w-60">
                        <label htmlFor="team" className="mb-1 block text-sm font-extrabold">Equipo</label>
                        <Select
                            id="team"
                            value={team.id}
                            onChange={(e) => router.get(route('desk.teams.show', Number(e.target.value)), { ...filters })}
                        >
                            {ledTeams.map((ledTeam) => (
                                <option key={ledTeam.id} value={ledTeam.id}>{ledTeam.name}</option>
                            ))}
                        </Select>
                    </div>
                )}
                <PeriodFields period={filters} onChange={changePeriod} error={errors.from ?? errors.to} />
            </div>

            <dl className="grid grid-cols-[repeat(auto-fit,minmax(190px,1fr))] gap-4">
                <MetricTile
                    highlighted
                    label="Tickets recibidos"
                    value={summary.total}
                    hint={unassigned > 0 ? `${unassigned} todavía sin tomar` : 'Todos tienen responsable'}
                />
                <MetricTile label="Resueltos" value={summary.resolved} hint={`${summary.open} siguen abiertos`} />
                <MetricTile
                    label="SLA cumplido"
                    value={formatPercent(summary.resolution_compliance)}
                    hint={`Primera respuesta a tiempo: ${formatPercent(summary.first_response_compliance)}`}
                />
                <MetricTile label="Primera respuesta promedio" value={formatMinutes(summary.average_first_response_minutes)} />
                <MetricTile label="Resolución promedio" value={formatMinutes(summary.average_resolution_minutes)} />
                <MetricTile
                    label="Calificación promedio"
                    value={formatRating(summary.average_rating)}
                    hint={`${summary.ratings} ${summary.ratings === 1 ? 'calificación' : 'calificaciones'}`}
                />
            </dl>

            <Card aria-labelledby="members-heading" className="mt-5 p-0">
                <div className="px-6 pt-6 pb-2">
                    <h2 id="members-heading" className="text-xl font-extrabold">Rendimiento de cada integrante</h2>
                    <p className="text-sm text-muted">
                        Compara a tus auxiliares. «Abiertos ahora» es su carga de hoy, sin importar el periodo.
                    </p>
                </div>
                <PerformanceTable
                    caption={`Rendimiento de los integrantes de ${team.name}`}
                    nameHeader="Integrante"
                    showOpenNow
                    emptyText="Tu equipo todavía no tiene integrantes."
                    rows={members.map((member) => ({
                        key: member.id,
                        name: member.name,
                        initials: member.initials,
                        openNow: member.open_now,
                        metrics: member.metrics,
                        details: (
                            <span className="mt-0.5 flex flex-wrap items-center gap-1.5">
                                {member.job_title ?? member.email}
                                {member.is_lead && <Badge tone="brand">Líder</Badge>}
                                {!member.is_active && <Badge>Inactivo</Badge>}
                            </span>
                        ),
                    }))}
                />
            </Card>

            <p className="mt-3 text-sm font-semibold text-muted">
                El periodo cuenta los tickets creados en esas fechas. Los tiempos son en horario laboral, como el SLA.
            </p>
        </AppLayout>
    );
}
