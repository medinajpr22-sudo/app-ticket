import Icon, { IconName } from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';

interface PlatformStats {
    open_tickets: number;
    unassigned_tickets: number;
    overdue_tickets: number;
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

const shortcuts: Array<{ label: string; description: string; icon: IconName; routeName: string }> = [
    { label: 'Bandeja', description: 'Los tickets de los equipos, los más urgentes primero.', icon: 'inbox', routeName: 'desk.tickets.index' },
    { label: 'Usuarios', description: 'Crea cuentas, asigna roles y equipos.', icon: 'user', routeName: 'management.users.index' },
    { label: 'Equipos', description: 'Define quién atiende cada tipo de solicitud.', icon: 'users', routeName: 'management.teams.index' },
    { label: 'Categorías', description: 'Lo que las personas pueden solicitar y a qué equipo llega.', icon: 'tag', routeName: 'management.categories.index' },
    { label: 'SLA y horarios', description: 'Tiempos de respuesta por prioridad y horario laboral.', icon: 'clock', routeName: 'management.service-levels.index' },
    { label: 'Plantillas', description: 'Saludo automático, acuse de recibo y respuestas rápidas.', icon: 'mail', routeName: 'management.message-templates.index' },
    { label: 'Roles y permisos', description: 'Qué puede hacer cada rol dentro de la plataforma.', icon: 'shield', routeName: 'management.roles.index' },
];

export default function Dashboard({ stats }: { stats: PlatformStats }) {
    const { auth } = usePage<PageProps>().props;
    const firstName = auth.user.name.split(' ')[0];

    const tiles: Array<{ label: string; value: number; href: string }> = [
        { label: 'Tickets abiertos', value: stats.open_tickets, href: route('desk.tickets.index') },
        { label: 'Sin asignar', value: stats.unassigned_tickets, href: route('desk.tickets.index', { view: 'unassigned' }) },
        { label: 'Vencidos', value: stats.overdue_tickets, href: route('desk.tickets.index') },
        { label: 'Personal de TI activo', value: stats.staff, href: route('management.users.index', { role: 'admin' }) },
    ];

    return (
        <AppLayout title="Panel general">
            <PageHeader title={`${greeting()}, ${firstName}`} description="Este es el estado general de la mesa de ayuda." />

            <ul className="grid grid-cols-[repeat(auto-fit,minmax(190px,1fr))] gap-4">
                {tiles.map((tile, index) => (
                    <li key={tile.label}>
                        <Link
                            href={tile.href}
                            className={
                                index === 0
                                    ? 'block rounded-2xl border border-white/30 bg-wine/92 px-5 py-4 text-white shadow-[0_12px_40px_rgb(86_19_23/0.25)] transition hover:-translate-y-0.5'
                                    : 'glass block px-5 py-4 transition hover:-translate-y-0.5'
                            }
                        >
                            <span className={`block text-sm font-bold ${index === 0 ? 'text-blush' : 'text-muted'}`}>
                                {tile.label}
                            </span>
                            <span className={`block text-4xl font-extrabold ${index === 2 && tile.value > 0 ? 'text-crimson-dark' : ''}`}>
                                {tile.value}
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>

            <p className="mt-3 text-sm font-semibold text-muted">
                {stats.active_users} cuentas activas · {stats.teams} equipos de TI
            </p>

            <h2 className="mt-8 mb-3.5 text-xl font-extrabold">Accesos rápidos</h2>
            <ul className="grid grid-cols-[repeat(auto-fit,minmax(260px,1fr))] gap-4">
                {shortcuts.map((shortcut) => (
                    <li key={shortcut.label}>
                        <Link
                            href={route(shortcut.routeName)}
                            className="glass flex items-center gap-4 p-5 transition hover:-translate-y-0.5"
                        >
                            <span className="grid size-11 shrink-0 place-items-center rounded-[14px] border border-crimson/20 bg-crimson/10 text-crimson-dark">
                                <Icon name={shortcut.icon} size={22} />
                            </span>
                            <span>
                                <span className="block font-extrabold">{shortcut.label}</span>
                                <span className="block text-sm font-semibold text-muted">{shortcut.description}</span>
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </AppLayout>
    );
}
