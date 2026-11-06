import Badge from '@/Components/ui/Badge';
import ButtonLink from '@/Components/ui/ButtonLink';
import EmptyState from '@/Components/ui/EmptyState';
import Icon from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import Select from '@/Components/ui/Select';
import AppLayout from '@/Layouts/AppLayout';
import { Category, SlaPolicy, Team } from '@/types';
import { router } from '@inertiajs/react';

export default function Index({
    categories,
    teams,
    defaultPolicy,
    filters,
}: {
    categories: Category[];
    teams: Team[];
    defaultPolicy: SlaPolicy | null;
    filters: { team: string };
}) {
    const filterByTeam = (team: string) =>
        router.get(route('management.categories.index'), team ? { team } : {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });

    const createHref = filters.team
        ? route('management.categories.create', { team: filters.team })
        : route('management.categories.create');

    return (
        <AppLayout title="Categorías">
            <PageHeader
                title="Categorías de atención"
                description="Lo que las personas pueden solicitar. Cada categoría llega a un equipo y usa una política de SLA."
                actions={
                    <ButtonLink href={createHref}>
                        <Icon name="plus" />
                        Nueva categoría
                    </ButtonLink>
                }
            />

            <div className="mb-5 w-full sm:w-72">
                <label htmlFor="team-filter" className="sr-only">
                    Filtrar por equipo
                </label>
                <Select id="team-filter" value={filters.team} onChange={(e) => filterByTeam(e.target.value)}>
                    <option value="">Todos los equipos</option>
                    {teams.map((team) => (
                        <option key={team.id} value={team.id}>
                            {team.name}
                        </option>
                    ))}
                </Select>
            </div>

            <div className="glass overflow-x-auto">
                <table className="w-full border-collapse text-left">
                    <caption className="sr-only">Categorías de atención</caption>
                    <thead>
                        <tr className="border-b border-line text-xs tracking-[0.07em] text-muted uppercase">
                            <th scope="col" className="px-3 py-3.5">Categoría</th>
                            <th scope="col" className="px-3 py-3.5">Equipo</th>
                            <th scope="col" className="hidden px-3 py-3.5 lg:table-cell">Tipo</th>
                            <th scope="col" className="hidden px-3 py-3.5 md:table-cell">SLA</th>
                            <th scope="col" className="px-3 py-3.5">Estado</th>
                            <th scope="col" className="px-3 py-3.5">
                                <span className="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {categories.map((category) => (
                            <tr key={category.id} className="border-b border-line last:border-0 hover:bg-white/55">
                                <td className="px-3 py-3.5">
                                    <div className="flex items-center gap-3">
                                        <span className="grid size-10 shrink-0 place-items-center rounded-xl border border-crimson/20 bg-crimson/10 text-crimson-dark">
                                            <Icon name={category.icon} />
                                        </span>
                                        <div className="min-w-0">
                                            <p className="font-extrabold">{category.name}</p>
                                            {category.description && (
                                                <p className="text-sm text-muted">{category.description}</p>
                                            )}
                                        </div>
                                    </div>
                                </td>
                                <td className="px-3 py-3.5 font-bold">{category.team?.name}</td>
                                <td className="hidden px-3 py-3.5 lg:table-cell">
                                    <Badge tone={category.default_ticket_type.value === 'incident' ? 'warning' : 'info'}>
                                        {category.default_ticket_type.label}
                                    </Badge>
                                </td>
                                <td className="hidden px-3 py-3.5 md:table-cell">
                                    {category.sla_policy ? (
                                        <span className="font-bold">{category.sla_policy.name}</span>
                                    ) : (
                                        <span className="text-muted">{defaultPolicy?.name} (predeterminada)</span>
                                    )}
                                </td>
                                <td className="px-3 py-3.5">
                                    {category.is_visible ? (
                                        <Badge tone="success" icon="check">Visible</Badge>
                                    ) : (
                                        <Badge icon="eyeOff">Oculta</Badge>
                                    )}
                                </td>
                                <td className="px-3 py-3.5 text-right">
                                    <ButtonLink
                                        href={route('management.categories.edit', category.id)}
                                        variant="secondary"
                                        size="sm"
                                    >
                                        <Icon name="pencil" size={16} />
                                        Editar
                                        <span className="sr-only"> la categoría {category.name}</span>
                                    </ButtonLink>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {categories.length === 0 && (
                    <EmptyState
                        icon="tag"
                        title="No hay categorías"
                        description={filters.team ? 'Este equipo todavía no tiene categorías.' : 'Crea la primera categoría de atención.'}
                    />
                )}
            </div>
        </AppLayout>
    );
}
