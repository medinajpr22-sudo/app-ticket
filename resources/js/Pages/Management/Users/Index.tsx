import Avatar from '@/Components/ui/Avatar';
import Badge, { BadgeTone } from '@/Components/ui/Badge';
import ButtonLink from '@/Components/ui/ButtonLink';
import EmptyState from '@/Components/ui/EmptyState';
import Icon from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import Pagination from '@/Components/ui/Pagination';
import Select from '@/Components/ui/Select';
import TextInput from '@/Components/ui/TextInput';
import AppLayout from '@/Layouts/AppLayout';
import { classNames } from '@/lib/classNames';
import { formatRelative } from '@/lib/formatDate';
import { Option, Paginated, RoleSlug, User } from '@/types';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

interface Filters {
    search: string;
    role: RoleSlug | '';
    status: '' | 'active' | 'inactive';
}

const roleTones: Record<RoleSlug, BadgeTone> = {
    super_admin: 'brand',
    admin: 'info',
    user: 'neutral',
};

const statusOptions: Array<{ value: Filters['status']; label: string }> = [
    { value: '', label: 'Todos' },
    { value: 'active', label: 'Activos' },
    { value: 'inactive', label: 'Inactivos' },
];

export default function Index({
    users,
    filters,
    roles,
}: {
    users: Paginated<User>;
    filters: Filters;
    roles: Option<RoleSlug>[];
}) {
    const [search, setSearch] = useState(filters.search);
    const isFirstRender = useRef(true);

    const applyFilters = (changes: Partial<Filters>) => {
        const query = Object.fromEntries(
            Object.entries({ ...filters, search, ...changes }).filter(([, value]) => value !== ''),
        );

        router.get(route('management.users.index'), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const timeout = setTimeout(() => applyFilters({ search }), 300);

        return () => clearTimeout(timeout);
    }, [search]);

    const hasFilters = filters.search !== '' || filters.role !== '' || filters.status !== '';

    return (
        <AppLayout title="Usuarios">
            <PageHeader
                title="Usuarios"
                description="Gestiona quién entra a la mesa de ayuda, con qué rol y en qué equipos de TI."
                actions={
                    <ButtonLink href={route('management.users.create')}>
                        <Icon name="plus" />
                        Nuevo usuario
                    </ButtonLink>
                }
            />

            <div className="mb-5 flex flex-wrap items-center gap-3">
                <div className="relative min-w-60 flex-1 md:max-w-sm">
                    <label htmlFor="search" className="sr-only">
                        Buscar por nombre o correo
                    </label>
                    <Icon name="search" className="pointer-events-none absolute top-3.5 left-4 text-muted" />
                    <TextInput
                        id="search"
                        type="search"
                        value={search}
                        placeholder="Buscar por nombre o correo"
                        className="rounded-full ps-11"
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>

                <div className="w-52">
                    <label htmlFor="role" className="sr-only">
                        Filtrar por rol
                    </label>
                    <Select
                        id="role"
                        value={filters.role}
                        onChange={(e) => applyFilters({ role: e.target.value as Filters['role'] })}
                    >
                        <option value="">Todos los roles</option>
                        {roles.map((role) => (
                            <option key={role.value} value={role.value}>
                                {role.label}
                            </option>
                        ))}
                    </Select>
                </div>

                <div role="group" aria-label="Filtrar por estado" className="flex flex-wrap gap-2">
                    {statusOptions.map((option) => (
                        <button
                            key={option.label}
                            type="button"
                            aria-pressed={filters.status === option.value}
                            onClick={() => applyFilters({ status: option.value })}
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
            </div>

            <div className="glass overflow-x-auto">
                <table className="w-full border-collapse text-left">
                    <caption className="sr-only">Usuarios de la mesa de ayuda</caption>
                    <thead>
                        <tr className="border-b border-line text-xs tracking-[0.07em] text-muted uppercase">
                            <th scope="col" className="px-3 py-3.5">Usuario</th>
                            <th scope="col" className="px-3 py-3.5">Rol</th>
                            <th scope="col" className="hidden px-3 py-3.5 2xl:table-cell">Área</th>
                            <th scope="col" className="hidden px-3 py-3.5 md:table-cell">Equipos</th>
                            <th scope="col" className="px-3 py-3.5">Estado</th>
                            <th scope="col" className="hidden px-3 py-3.5 2xl:table-cell">Último acceso</th>
                            <th scope="col" className="px-3 py-3.5">
                                <span className="sr-only">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.data.map((user) => (
                            <tr key={user.id} className="border-b border-line last:border-0 hover:bg-white/55">
                                <td className="px-3 py-3.5">
                                    <div className="flex items-center gap-3">
                                        <Avatar initials={user.initials} />
                                        <div className="min-w-0">
                                            <p className="font-extrabold">{user.name}</p>
                                            <p className="truncate text-sm text-muted">{user.email}</p>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-3 py-3.5">
                                    {user.role && (
                                        <Badge tone={roleTones[user.role.slug]}>{user.role.label}</Badge>
                                    )}
                                </td>
                                <td className="hidden px-3 py-3.5 2xl:table-cell">
                                    <p className="font-bold">{user.department?.name ?? '—'}</p>
                                    <p className="text-sm text-muted">{user.location?.name}</p>
                                </td>
                                <td className="hidden px-3 py-3.5 md:table-cell">
                                    {user.teams && user.teams.length > 0 ? (
                                        <ul className="flex max-w-60 flex-wrap gap-1.5">
                                            {user.teams.map((team) => (
                                                <li key={team.id}>
                                                    <Badge icon={team.icon}>
                                                        {team.name}
                                                        {team.is_lead && ' · Líder'}
                                                    </Badge>
                                                </li>
                                            ))}
                                        </ul>
                                    ) : (
                                        <span className="text-muted">—</span>
                                    )}
                                </td>
                                <td className="px-3 py-3.5">
                                    {user.is_active ? (
                                        <Badge tone="success" icon="check">Activo</Badge>
                                    ) : (
                                        <Badge icon="lock">Inactivo</Badge>
                                    )}
                                </td>
                                <td className="hidden px-3 py-3.5 text-sm font-semibold text-muted 2xl:table-cell">
                                    {user.last_login_at ? formatRelative(user.last_login_at) : 'Nunca'}
                                </td>
                                <td className="px-3 py-3.5 text-right">
                                    <ButtonLink
                                        href={route('management.users.edit', user.id)}
                                        variant="secondary"
                                        size="sm"
                                    >
                                        <Icon name="pencil" size={16} />
                                        Editar
                                        <span className="sr-only"> a {user.name}</span>
                                    </ButtonLink>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {users.data.length === 0 && (
                    <EmptyState
                        icon="search"
                        title="No encontramos usuarios"
                        description={hasFilters ? 'Prueba con otra búsqueda o quita los filtros.' : 'Crea el primer usuario de la plataforma.'}
                    />
                )}
            </div>

            <Pagination meta={users.meta} />
        </AppLayout>
    );
}
