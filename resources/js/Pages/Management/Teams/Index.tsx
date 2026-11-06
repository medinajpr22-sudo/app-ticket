import Avatar from '@/Components/ui/Avatar';
import Badge from '@/Components/ui/Badge';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import EmptyState from '@/Components/ui/EmptyState';
import Icon from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { DescribedOption, Team, TeamTypeValue } from '@/types';

export default function Index({
    teams,
    types,
}: {
    teams: Team[];
    types: DescribedOption<TeamTypeValue>[];
}) {
    return (
        <AppLayout title="Equipos de TI">
            <PageHeader
                title="Equipos de TI"
                description="Cada equipo atiende sus propias solicitudes. Aquí defines quién lo integra y quién lo lidera."
                actions={
                    <ButtonLink href={route('management.teams.create')}>
                        <Icon name="plus" />
                        Nuevo equipo
                    </ButtonLink>
                }
            />

            {teams.length === 0 && (
                <Card>
                    <EmptyState
                        icon="users"
                        title="Todavía no hay equipos"
                        description="Crea el primer equipo para empezar a repartir las solicitudes."
                    />
                </Card>
            )}

            {types.map((type) => {
                const teamsOfType = teams.filter((team) => team.type.value === type.value);

                if (teamsOfType.length === 0) {
                    return null;
                }

                return (
                    <section key={type.value} aria-labelledby={`type-${type.value}`} className="mb-8">
                        <h2 id={`type-${type.value}`} className="text-xl font-extrabold">
                            {type.label}
                        </h2>
                        <p className="mb-4 text-muted">{type.description}</p>

                        <ul className="grid grid-cols-[repeat(auto-fill,minmax(min(100%,340px),1fr))] items-start gap-4">
                            {teamsOfType.map((team) => (
                                <li key={team.id}>
                                    <TeamCard team={team} />
                                </li>
                            ))}
                        </ul>
                    </section>
                );
            })}
        </AppLayout>
    );
}

function TeamCard({ team }: { team: Team }) {
    const members = team.members ?? [];

    return (
        <Card className={team.is_active ? '' : 'opacity-75'}>
            <div className="flex items-start gap-3">
                <span className="grid size-11 shrink-0 place-items-center rounded-[14px] border border-crimson/20 bg-crimson/10 text-crimson-dark">
                    <Icon name={team.icon} size={22} />
                </span>
                <div className="min-w-0 flex-1">
                    <h3 className="text-lg font-extrabold">{team.name}</h3>
                    {team.description && <p className="text-sm text-muted">{team.description}</p>}
                </div>
                {!team.is_active && <Badge icon="lock">Inactivo</Badge>}
            </div>

            <h4 className="mt-5 mb-2 text-sm font-extrabold">Integrantes ({members.length})</h4>
            {members.length > 0 ? (
                <ul className="flex flex-col gap-2">
                    {members.map((member) => (
                        <li key={member.id} className="flex items-center gap-2.5">
                            <Avatar initials={member.initials} size="sm" />
                            <span className="font-bold">{member.name}</span>
                            {member.is_lead && <Badge tone="brand" icon="star">Líder</Badge>}
                        </li>
                    ))}
                </ul>
            ) : (
                <p className="text-sm text-muted">Sin integrantes todavía.</p>
            )}

            <div className="mt-5 flex justify-end">
                <ButtonLink href={route('management.teams.edit', team.id)} variant="secondary" size="sm">
                    <Icon name="pencil" size={16} />
                    Editar
                    <span className="sr-only"> el equipo {team.name}</span>
                </ButtonLink>
            </div>
        </Card>
    );
}
