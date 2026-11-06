import PriorityBadge from '@/Components/Ticketing/PriorityBadge';
import Badge from '@/Components/ui/Badge';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import Icon from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formatDuration } from '@/lib/formatDuration';
import { BusinessCalendar, SlaPolicy } from '@/types';

const weekdays = [1, 2, 3, 4, 5, 6, 7];
const weekdayInitials = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
const weekdayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

export default function Index({
    policies,
    calendars,
}: {
    policies: SlaPolicy[];
    calendars: BusinessCalendar[];
}) {
    return (
        <AppLayout title="SLA y horarios">
            <PageHeader
                title="SLA y horarios"
                description="Cuánto tiempo hay para responder y resolver cada solicitud. El tiempo solo corre en horario laboral."
                actions={
                    <>
                        <ButtonLink href={route('management.service-levels.calendars.create')} variant="secondary">
                            <Icon name="calendar" />
                            Nuevo horario
                        </ButtonLink>
                        <ButtonLink href={route('management.service-levels.policies.create')}>
                            <Icon name="plus" />
                            Nueva política
                        </ButtonLink>
                    </>
                }
            />

            <section aria-labelledby="policies-heading" className="mb-10">
                <h2 id="policies-heading" className="text-xl font-extrabold">
                    Políticas de SLA
                </h2>
                <p className="mb-4 text-muted">
                    Cada categoría usa la política predeterminada, salvo que elija otra.
                </p>

                <ul className="grid grid-cols-[repeat(auto-fill,minmax(min(100%,380px),1fr))] items-start gap-4">
                    {policies.map((policy) => (
                        <li key={policy.id}>
                            <PolicyCard policy={policy} />
                        </li>
                    ))}
                </ul>
            </section>

            <section aria-labelledby="calendars-heading">
                <h2 id="calendars-heading" className="text-xl font-extrabold">
                    Horarios laborales
                </h2>
                <p className="mb-4 text-muted">
                    Días y horas en que corre el tiempo de los SLA. Los festivos no cuentan.
                </p>

                <ul className="grid grid-cols-[repeat(auto-fill,minmax(min(100%,380px),1fr))] items-start gap-4">
                    {calendars.map((calendar) => (
                        <li key={calendar.id}>
                            <CalendarCard calendar={calendar} />
                        </li>
                    ))}
                </ul>
            </section>
        </AppLayout>
    );
}

function PolicyCard({ policy }: { policy: SlaPolicy }) {
    return (
        <Card>
            <div className="flex items-start gap-3">
                <div className="min-w-0 flex-1">
                    <h3 className="text-lg font-extrabold">{policy.name}</h3>
                    {policy.description && <p className="text-sm text-muted">{policy.description}</p>}
                </div>
                {policy.is_default && (
                    <Badge tone="brand" icon="star">
                        Predeterminada
                    </Badge>
                )}
            </div>

            <table className="mt-4 w-full text-left text-sm">
                <caption className="sr-only">Tiempos de la política {policy.name}</caption>
                <thead>
                    <tr className="border-b border-line text-xs tracking-[0.07em] text-muted uppercase">
                        <th scope="col" className="py-2 pe-2">Prioridad</th>
                        <th scope="col" className="px-2 py-2">Primera respuesta</th>
                        <th scope="col" className="py-2 ps-2">Resolución</th>
                    </tr>
                </thead>
                <tbody>
                    {policy.targets?.map((target) => (
                        <tr key={target.priority} className="border-b border-line last:border-0">
                            <th scope="row" className="py-2 pe-2 font-normal">
                                <PriorityBadge priority={target.priority} label={target.label} />
                            </th>
                            <td className="px-2 py-2 font-bold">{formatDuration(target.first_response_minutes)}</td>
                            <td className="py-2 ps-2 font-bold">{formatDuration(target.resolution_minutes)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>

            <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-semibold text-muted">
                <span className="flex items-center gap-1.5">
                    <Icon name="calendar" size={16} />
                    {policy.business_calendar?.name}
                </span>
                <span className="flex items-center gap-1.5">
                    <Icon name="tag" size={16} />
                    {policy.is_default
                        ? 'Categorías sin política propia'
                        : `${policy.categories_count} ${policy.categories_count === 1 ? 'categoría' : 'categorías'}`}
                </span>
                <ButtonLink
                    href={route('management.service-levels.policies.edit', policy.id)}
                    variant="secondary"
                    size="sm"
                    className="ms-auto"
                >
                    <Icon name="pencil" size={16} />
                    Editar
                    <span className="sr-only"> la política {policy.name}</span>
                </ButtonLink>
            </div>
        </Card>
    );
}

function CalendarCard({ calendar }: { calendar: BusinessCalendar }) {
    const hoursByWeekday = new Map((calendar.hours ?? []).map((hour) => [hour.weekday, hour]));

    return (
        <Card>
            <div className="flex items-start gap-3">
                <div className="min-w-0 flex-1">
                    <h3 className="text-lg font-extrabold">{calendar.name}</h3>
                    <p className="flex items-center gap-1.5 text-sm text-muted">
                        <Icon name="globe" size={16} />
                        {calendar.timezone}
                    </p>
                </div>
                {calendar.is_default && (
                    <Badge tone="brand" icon="star">
                        Predeterminado
                    </Badge>
                )}
            </div>

            <ul className="mt-4 grid grid-cols-7 gap-1.5" aria-label="Horario semanal">
                {weekdays.map((weekday, index) => {
                    const hours = hoursByWeekday.get(weekday);

                    return (
                        <li
                            key={weekday}
                            className={`rounded-xl border px-1 py-2 text-center text-xs font-bold ${hours ? 'border-wine/30 bg-white/80' : 'border-line text-muted'}`}
                        >
                            <span aria-hidden="true">
                                <span className="block text-sm font-extrabold">{weekdayInitials[index]}</span>
                                {hours ? (
                                    <>
                                        <span className="block">{hours.opens_at}</span>
                                        <span className="block">{hours.closes_at}</span>
                                    </>
                                ) : (
                                    <span className="block">Cerrado</span>
                                )}
                            </span>
                            <span className="sr-only">
                                {weekdayNames[index]}: {hours ? `de ${hours.opens_at} a ${hours.closes_at}` : 'no laborable'}
                            </span>
                        </li>
                    );
                })}
            </ul>

            <div className="mt-4 flex items-center gap-4 text-sm font-semibold text-muted">
                <span className="flex items-center gap-1.5">
                    <Icon name="calendar" size={16} />
                    {calendar.holidays_count} {calendar.holidays_count === 1 ? 'festivo' : 'festivos'}
                </span>
                <ButtonLink
                    href={route('management.service-levels.calendars.edit', calendar.id)}
                    variant="secondary"
                    size="sm"
                    className="ms-auto"
                >
                    <Icon name="pencil" size={16} />
                    Editar
                    <span className="sr-only"> el horario {calendar.name}</span>
                </ButtonLink>
            </div>
        </Card>
    );
}
