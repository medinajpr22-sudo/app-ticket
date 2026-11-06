import Card from '@/Components/ui/Card';
import Icon, { IconName } from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';

interface ManagementStats {
    users: number;
    active_users: number;
    staff: number;
    teams: number;
}

function greeting(): string {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Buenos días';
    }

    return hour < 19 ? 'Buenas tardes' : 'Buenas noches';
}

export default function Dashboard({ stats }: { stats: ManagementStats | null }) {
    const { auth } = usePage<PageProps>().props;
    const firstName = auth.user.name.split(' ')[0];

    const tiles: Array<{ label: string; value: number }> = stats
        ? [
              { label: 'Usuarios registrados', value: stats.users },
              { label: 'Cuentas activas', value: stats.active_users },
              { label: 'Personal de TI', value: stats.staff },
              { label: 'Equipos activos', value: stats.teams },
          ]
        : [];

    const shortcuts: Array<{ label: string; description: string; icon: IconName; href: string }> = [
        {
            label: 'Usuarios',
            description: 'Crea cuentas, asigna roles y equipos.',
            icon: 'user',
            href: route('management.users.index'),
        },
        {
            label: 'Equipos',
            description: 'Define quién atiende cada tipo de solicitud.',
            icon: 'users',
            href: route('management.teams.index'),
        },
        {
            label: 'Categorías',
            description: 'Lo que las personas pueden solicitar y a qué equipo llega.',
            icon: 'tag',
            href: route('management.categories.index'),
        },
        {
            label: 'SLA y horarios',
            description: 'Tiempos de respuesta por prioridad y horario laboral.',
            icon: 'clock',
            href: route('management.service-levels.index'),
        },
        {
            label: 'Plantillas',
            description: 'Saludo automático, acuse de recibo y respuestas rápidas.',
            icon: 'mail',
            href: route('management.message-templates.index'),
        },
        {
            label: 'Roles y permisos',
            description: 'Qué puede hacer cada rol dentro de la plataforma.',
            icon: 'shield',
            href: route('management.roles.index'),
        },
    ];

    return (
        <AppLayout title="Inicio">
            <PageHeader
                title={`${greeting()}, ${firstName}`}
                description={
                    stats
                        ? 'Este es el estado general de la plataforma.'
                        : 'Muy pronto podrás crear y seguir tus tickets desde aquí.'
                }
            />

            {stats && (
                <>
                    <ul className="grid grid-cols-[repeat(auto-fit,minmax(190px,1fr))] gap-4">
                        {tiles.map((tile, index) => (
                            <li
                                key={tile.label}
                                className={
                                    index === 0
                                        ? 'rounded-2xl border border-white/30 bg-wine/92 px-5 py-4 text-white shadow-[0_12px_40px_rgb(86_19_23/0.25)]'
                                        : 'glass px-5 py-4'
                                }
                            >
                                <p className={`text-sm font-bold ${index === 0 ? 'text-blush' : 'text-muted'}`}>
                                    {tile.label}
                                </p>
                                <p className="text-4xl font-extrabold">{tile.value}</p>
                            </li>
                        ))}
                    </ul>

                    <h2 className="mt-8 mb-3.5 text-xl font-extrabold">Administración</h2>
                    <ul className="grid grid-cols-[repeat(auto-fit,minmax(260px,1fr))] gap-4">
                        {shortcuts.map((shortcut) => (
                            <li key={shortcut.label}>
                                <Link
                                    href={shortcut.href}
                                    className="glass flex items-center gap-4 p-5 transition hover:-translate-y-0.5"
                                >
                                    <span className="grid size-11 place-items-center rounded-[14px] border border-crimson/20 bg-crimson/10 text-crimson-dark">
                                        <Icon name={shortcut.icon} size={22} />
                                    </span>
                                    <span>
                                        <span className="block font-extrabold">{shortcut.label}</span>
                                        <span className="block text-sm font-semibold text-muted">
                                            {shortcut.description}
                                        </span>
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </>
            )}

            {!stats && (
                <Card className="text-center">
                    <Icon name="ticket" size={34} className="mx-auto mb-2 text-crimson-dark" />
                    <p className="font-extrabold">Tu portal de ayuda está en construcción</p>
                    <p className="text-muted">
                        Si tienes una urgencia, comunícate con la mesa de ayuda.
                    </p>
                </Card>
            )}
        </AppLayout>
    );
}
