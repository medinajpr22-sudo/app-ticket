import Card from '@/Components/ui/Card';
import Checkbox from '@/Components/ui/Checkbox';
import Icon from '@/Components/ui/Icon';
import InputError from '@/Components/ui/InputError';
import PageHeader from '@/Components/ui/PageHeader';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import SecondaryButton from '@/Components/ui/SecondaryButton';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { RoleSlug } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface RoleSummary {
    slug: RoleSlug;
    label: string;
    description: string;
    users_count: number;
    is_locked: boolean;
    permissions: string[];
}

interface PermissionGroup {
    value: string;
    label: string;
    permissions: Array<{ slug: string; label: string; is_reserved: boolean }>;
}

export default function Index({ roles, groups }: { roles: RoleSummary[]; groups: PermissionGroup[] }) {
    const editableRoles = roles.filter((role) => !role.is_locked);
    const savedPermissions = Object.fromEntries(editableRoles.map((role) => [role.slug, role.permissions]));

    const form = useForm<{ permissions: Record<string, string[]> }>({
        permissions: savedPermissions,
    });

    const hasUnsavedChanges = editableRoles.some((role) => {
        const selected = form.data.permissions[role.slug] ?? [];

        return (
            selected.length !== role.permissions.length ||
            selected.some((permission) => !role.permissions.includes(permission))
        );
    });

    const permissionsError = Object.entries(form.errors as Record<string, string | undefined>).find(([field]) =>
        field.startsWith('permissions'),
    )?.[1];

    const togglePermission = (role: RoleSlug, permission: string, isGranted: boolean) => {
        const current = form.data.permissions[role] ?? [];

        form.setData('permissions', {
            ...form.data.permissions,
            [role]: isGranted ? [...current, permission] : current.filter((slug) => slug !== permission),
        });
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.put(route('management.roles.update'), formSubmitOptions);
    };

    return (
        <AppLayout title="Roles y permisos">
            <PageHeader
                title="Roles y permisos"
                description="Define qué puede hacer cada rol. El super administrador siempre tiene todos los permisos."
            />

            <ul className="mb-6 grid grid-cols-[repeat(auto-fit,minmax(min(100%,240px),1fr))] gap-4">
                {roles.map((role) => (
                    <li key={role.slug} className="glass px-5 py-4">
                        <p className="flex items-center gap-2 font-extrabold">
                            {role.label}
                            {role.is_locked && <Icon name="lock" size={16} className="text-crimson-dark" />}
                        </p>
                        <p className="text-sm text-muted">{role.description}</p>
                        <p className="mt-2 text-sm font-bold">
                            {role.users_count} {role.users_count === 1 ? 'persona' : 'personas'}
                        </p>
                    </li>
                ))}
            </ul>

            <form onSubmit={submit} noValidate>
                <Card className="overflow-x-auto p-0">
                    <table className="w-full border-collapse text-left">
                        <caption className="sr-only">Permisos por rol</caption>
                        <thead>
                            <tr className="border-b border-line text-xs tracking-[0.07em] text-muted uppercase">
                                <th scope="col" className="px-4 py-3.5">Permiso</th>
                                {roles.map((role) => (
                                    <th key={role.slug} scope="col" className="px-4 py-3.5 text-center">
                                        {role.label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        {groups.map((group) => (
                            <tbody key={group.value}>
                                <tr className="bg-wine/5">
                                    <th
                                        scope="colgroup"
                                        colSpan={roles.length + 1}
                                        className="px-4 py-2.5 text-sm font-extrabold text-wine"
                                    >
                                        {group.label}
                                    </th>
                                </tr>
                                {group.permissions.map((permission) => (
                                    <tr key={permission.slug} className="border-b border-line last:border-0 hover:bg-white/55">
                                        <th scope="row" className="px-4 py-3 font-bold">
                                            {permission.label}
                                            {permission.is_reserved && (
                                                <span className="block text-xs font-semibold text-muted">
                                                    Solo el super administrador
                                                </span>
                                            )}
                                        </th>
                                        {roles.map((role) => (
                                            <td key={role.slug} className="px-4 py-3 text-center">
                                                {role.is_locked || permission.is_reserved ? (
                                                    <span className="inline-grid size-5 place-items-center text-muted">
                                                        <Icon
                                                            name={role.is_locked ? 'check' : 'lock'}
                                                            size={18}
                                                            className={role.is_locked ? 'text-success' : ''}
                                                        />
                                                        <span className="sr-only">
                                                            {role.is_locked ? 'Siempre permitido' : 'No disponible para este rol'}
                                                        </span>
                                                    </span>
                                                ) : (
                                                    <Checkbox
                                                        checked={form.data.permissions[role.slug]?.includes(permission.slug) ?? false}
                                                        aria-label={`${permission.label} para ${role.label}`}
                                                        onChange={(e) => togglePermission(role.slug, permission.slug, e.target.checked)}
                                                    />
                                                )}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        ))}
                    </table>
                </Card>

                <InputError message={permissionsError} />

                <div className="mt-5 flex flex-wrap items-center justify-end gap-3">
                    {hasUnsavedChanges && (
                        <p role="status" className="me-auto text-sm font-bold text-warning">
                            Tienes cambios sin guardar.
                        </p>
                    )}
                    <SecondaryButton
                        onClick={() => form.setData('permissions', savedPermissions)}
                        disabled={!hasUnsavedChanges}
                    >
                        Descartar cambios
                    </SecondaryButton>
                    <PrimaryButton disabled={form.processing || !hasUnsavedChanges}>Guardar permisos</PrimaryButton>
                </div>
            </form>
        </AppLayout>
    );
}
