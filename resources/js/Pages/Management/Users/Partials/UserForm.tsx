import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import Checkbox from '@/Components/ui/Checkbox';
import FormField from '@/Components/ui/FormField';
import Icon from '@/Components/ui/Icon';
import InputError from '@/Components/ui/InputError';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import Select from '@/Components/ui/Select';
import TextInput from '@/Components/ui/TextInput';
import { classNames } from '@/lib/classNames';
import { Department, Location, Role, Team } from '@/types';
import { InertiaFormProps } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export interface UserFormData {
    role_id: number | '';
    name: string;
    email: string;
    job_title: string;
    phone: string;
    department_id: number | '';
    location_id: number | '';
    is_active: boolean;
    password: string;
    team_ids: number[];
}

export default function UserForm({
    form,
    roles,
    departments,
    locations,
    teams,
    isEditing,
    onSubmit,
}: {
    form: InertiaFormProps<UserFormData>;
    roles: Role[];
    departments: Department[];
    locations: Location[];
    teams: Team[];
    isEditing: boolean;
    onSubmit: FormEventHandler;
}) {
    const { data, setData, errors, processing } = form;
    const selectedRole = roles.find((role) => role.id === data.role_id);

    const selectRole = (role: Role) =>
        setData((current) => ({
            ...current,
            role_id: role.id,
            team_ids: role.is_staff ? current.team_ids : [],
        }));

    const toggleTeam = (teamId: number, isMember: boolean) =>
        setData(
            'team_ids',
            isMember
                ? [...data.team_ids, teamId]
                : data.team_ids.filter((id) => id !== teamId),
        );

    return (
        <form onSubmit={onSubmit} noValidate className="flex max-w-5xl flex-col gap-5">
            <Card aria-labelledby="personal-heading">
                <h2 id="personal-heading" className="mb-4 text-xl font-extrabold">
                    Datos personales
                </h2>
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField id="name" label="Nombre completo" error={errors.name}>
                        <TextInput
                            id="name"
                            value={data.name}
                            autoComplete="off"
                            aria-invalid={errors.name ? true : undefined}
                            onChange={(e) => setData('name', e.target.value)}
                            isFocused={!isEditing}
                        />
                    </FormField>
                    <FormField id="email" label="Correo corporativo" error={errors.email}>
                        <TextInput
                            id="email"
                            type="email"
                            value={data.email}
                            autoComplete="off"
                            placeholder="nombre@empresa.com"
                            aria-invalid={errors.email ? true : undefined}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                    </FormField>
                    <FormField id="job_title" label="Cargo" optional error={errors.job_title}>
                        <TextInput
                            id="job_title"
                            value={data.job_title}
                            aria-invalid={errors.job_title ? true : undefined}
                            onChange={(e) => setData('job_title', e.target.value)}
                        />
                    </FormField>
                    <FormField id="phone" label="Teléfono o extensión" optional error={errors.phone}>
                        <TextInput
                            id="phone"
                            type="tel"
                            value={data.phone}
                            aria-invalid={errors.phone ? true : undefined}
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                    </FormField>
                </div>
            </Card>

            <Card aria-labelledby="access-heading">
                <h2 id="access-heading" className="mb-4 text-xl font-extrabold">
                    Rol y acceso
                </h2>

                <fieldset>
                    <legend className="mb-2 text-sm font-extrabold">Rol</legend>
                    <div className="grid gap-3 md:grid-cols-3">
                        {roles.map((role) => (
                            <label
                                key={role.id}
                                className={classNames(
                                    'flex cursor-pointer flex-col gap-1 rounded-2xl border-[1.5px] p-4 transition has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus',
                                    data.role_id === role.id
                                        ? 'border-wine bg-wine text-white'
                                        : 'border-field bg-white/85 hover:border-crimson',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="role_id"
                                    value={role.id}
                                    checked={data.role_id === role.id}
                                    onChange={() => selectRole(role)}
                                    className="sr-only"
                                />
                                <span className="font-extrabold">{role.label}</span>
                                <span
                                    className={classNames(
                                        'text-sm font-semibold',
                                        data.role_id === role.id ? 'text-blush' : 'text-muted',
                                    )}
                                >
                                    {role.description}
                                </span>
                            </label>
                        ))}
                    </div>
                    <InputError message={errors.role_id} />
                </fieldset>

                <div className="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label className="flex min-h-12 items-center gap-3 font-bold">
                            <Checkbox
                                checked={data.is_active}
                                aria-describedby="is_active-hint"
                                onChange={(e) => setData('is_active', e.target.checked)}
                            />
                            Cuenta activa
                        </label>
                        <p id="is_active-hint" className="text-sm font-semibold text-muted">
                            Una cuenta inactiva no puede iniciar sesión.
                        </p>
                        <InputError message={errors.is_active} />
                    </div>

                    <FormField
                        id="password"
                        label={isEditing ? 'Nueva contraseña' : 'Contraseña inicial'}
                        optional
                        error={errors.password}
                        hint={
                            isEditing
                                ? 'Déjala vacía para mantener la actual.'
                                : 'Déjala vacía si la persona entrará con su cuenta corporativa o recuperará su contraseña por correo.'
                        }
                    >
                        <TextInput
                            id="password"
                            type="password"
                            value={data.password}
                            autoComplete="new-password"
                            aria-describedby="password-hint"
                            aria-invalid={errors.password ? true : undefined}
                            onChange={(e) => setData('password', e.target.value)}
                        />
                    </FormField>
                </div>
            </Card>

            <Card aria-labelledby="organization-heading">
                <h2 id="organization-heading" className="mb-4 text-xl font-extrabold">
                    Área y sede
                </h2>
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField id="department_id" label="Área o departamento" optional error={errors.department_id}>
                        <Select
                            id="department_id"
                            value={data.department_id}
                            onChange={(e) =>
                                setData('department_id', e.target.value ? Number(e.target.value) : '')
                            }
                        >
                            <option value="">Sin asignar</option>
                            {departments.map((department) => (
                                <option key={department.id} value={department.id}>
                                    {department.name}
                                </option>
                            ))}
                        </Select>
                    </FormField>
                    <FormField id="location_id" label="Sede" optional error={errors.location_id}>
                        <Select
                            id="location_id"
                            value={data.location_id}
                            onChange={(e) =>
                                setData('location_id', e.target.value ? Number(e.target.value) : '')
                            }
                        >
                            <option value="">Sin asignar</option>
                            {locations.map((location) => (
                                <option key={location.id} value={location.id}>
                                    {location.name}
                                </option>
                            ))}
                        </Select>
                    </FormField>
                </div>
            </Card>

            {selectedRole?.is_staff && (
                <Card aria-labelledby="teams-heading">
                    <h2 id="teams-heading" className="text-xl font-extrabold">
                        Equipos de TI
                    </h2>
                    <p className="mb-4 text-muted">
                        Recibirá los tickets de los equipos que marques. Los líderes se eligen desde cada equipo.
                    </p>
                    <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {teams.map((team) => {
                            const isMember = data.team_ids.includes(team.id);

                            return (
                                <li key={team.id}>
                                    <label
                                        className={classNames(
                                            'flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border-[1.5px] p-3 transition',
                                            isMember
                                                ? 'border-crimson bg-white shadow-[0_0_0_2px_var(--color-crimson)]'
                                                : 'border-field bg-white/70 hover:border-crimson',
                                        )}
                                    >
                                        <Checkbox
                                            checked={isMember}
                                            onChange={(e) => toggleTeam(team.id, e.target.checked)}
                                        />
                                        <Icon name={team.icon} className="text-crimson-dark" />
                                        <span className="leading-tight">
                                            <span className="block font-extrabold">{team.name}</span>
                                            <span className="block text-xs font-semibold text-muted">
                                                {team.is_active ? team.type.label : 'Equipo inactivo'}
                                            </span>
                                        </span>
                                    </label>
                                </li>
                            );
                        })}
                    </ul>
                    <InputError message={errors.team_ids} />
                </Card>
            )}

            <div className="flex flex-wrap justify-end gap-3">
                <ButtonLink href={route('management.users.index')} variant="secondary">
                    Cancelar
                </ButtonLink>
                <PrimaryButton disabled={processing}>
                    {isEditing ? 'Guardar cambios' : 'Crear usuario'}
                </PrimaryButton>
            </div>
        </form>
    );
}
