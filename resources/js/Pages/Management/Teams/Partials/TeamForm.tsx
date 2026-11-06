import Avatar from '@/Components/ui/Avatar';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import Checkbox from '@/Components/ui/Checkbox';
import FormField from '@/Components/ui/FormField';
import Icon, { IconName } from '@/Components/ui/Icon';
import InputError from '@/Components/ui/InputError';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import TextArea from '@/Components/ui/TextArea';
import TextInput from '@/Components/ui/TextInput';
import { classNames } from '@/lib/classNames';
import { DescribedOption, Option, TeamTypeValue, User } from '@/types';
import { InertiaFormProps } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

export interface TeamMemberFormData {
    user_id: number;
    is_lead: boolean;
}

export interface TeamFormData {
    name: string;
    type: TeamTypeValue;
    icon: IconName;
    description: string;
    is_active: boolean;
    members: TeamMemberFormData[];
}

export default function TeamForm({
    form,
    types,
    icons,
    staff,
    isEditing,
    onSubmit,
}: {
    form: InertiaFormProps<TeamFormData>;
    types: DescribedOption<TeamTypeValue>[];
    icons: Option<IconName>[];
    staff: User[];
    isEditing: boolean;
    onSubmit: FormEventHandler;
}) {
    const { data, setData, errors, processing } = form;
    const [staffSearch, setStaffSearch] = useState('');

    const membership = (userId: number) => data.members.find((member) => member.user_id === userId);

    const toggleMember = (userId: number, isMember: boolean) =>
        setData(
            'members',
            isMember
                ? [...data.members, { user_id: userId, is_lead: false }]
                : data.members.filter((member) => member.user_id !== userId),
        );

    const toggleLead = (userId: number, isLead: boolean) =>
        setData(
            'members',
            data.members.map((member) =>
                member.user_id === userId ? { ...member, is_lead: isLead } : member,
            ),
        );

    const visibleStaff = staff.filter((person) =>
        person.name.toLowerCase().includes(staffSearch.trim().toLowerCase()),
    );

    const memberErrors = Object.entries(errors)
        .filter(([field]) => field.startsWith('members'))
        .map(([, message]) => message);

    return (
        <form onSubmit={onSubmit} noValidate className="flex max-w-5xl flex-col gap-5">
            <Card aria-labelledby="general-heading">
                <h2 id="general-heading" className="mb-4 text-xl font-extrabold">
                    Información del equipo
                </h2>

                <div className="grid gap-4 md:grid-cols-2">
                    <FormField id="name" label="Nombre" error={errors.name}>
                        <TextInput
                            id="name"
                            value={data.name}
                            placeholder="Redes y Conectividad"
                            aria-invalid={errors.name ? true : undefined}
                            onChange={(e) => setData('name', e.target.value)}
                            isFocused={!isEditing}
                        />
                    </FormField>

                    <div>
                        <label className="flex min-h-12 items-center gap-3 font-bold md:mt-7">
                            <Checkbox
                                checked={data.is_active}
                                aria-describedby="is_active-hint"
                                onChange={(e) => setData('is_active', e.target.checked)}
                            />
                            Equipo activo
                        </label>
                        <p id="is_active-hint" className="text-sm font-semibold text-muted">
                            Un equipo inactivo deja de recibir tickets nuevos.
                        </p>
                    </div>

                    <FormField
                        id="description"
                        label="Descripción"
                        optional
                        error={errors.description}
                        className="md:col-span-2"
                    >
                        <TextArea
                            id="description"
                            rows={2}
                            value={data.description}
                            placeholder="WiFi, VPN, telefonía y enlaces"
                            onChange={(e) => setData('description', e.target.value)}
                        />
                    </FormField>
                </div>

                <fieldset className="mt-5">
                    <legend className="mb-2 text-sm font-extrabold">Tipo de equipo</legend>
                    <div className="grid gap-3 md:grid-cols-2">
                        {types.map((type) => (
                            <label
                                key={type.value}
                                className={classNames(
                                    'flex cursor-pointer flex-col gap-1 rounded-2xl border-[1.5px] p-4 transition has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus',
                                    data.type === type.value
                                        ? 'border-wine bg-wine text-white'
                                        : 'border-field bg-white/85 hover:border-crimson',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="type"
                                    value={type.value}
                                    checked={data.type === type.value}
                                    onChange={() => setData('type', type.value)}
                                    className="sr-only"
                                />
                                <span className="font-extrabold">{type.label}</span>
                                <span
                                    className={classNames(
                                        'text-sm font-semibold',
                                        data.type === type.value ? 'text-blush' : 'text-muted',
                                    )}
                                >
                                    {type.description}
                                </span>
                            </label>
                        ))}
                    </div>
                    <InputError message={errors.type} />
                </fieldset>

                <fieldset className="mt-5">
                    <legend className="mb-2 text-sm font-extrabold">Icono</legend>
                    <div className="flex flex-wrap gap-2">
                        {icons.map((icon) => (
                            <label
                                key={icon.value}
                                title={icon.label}
                                className={classNames(
                                    'grid size-12 cursor-pointer place-items-center rounded-[14px] border-[1.5px] transition has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus',
                                    data.icon === icon.value
                                        ? 'border-wine bg-wine text-white'
                                        : 'border-field bg-white/85 text-crimson-dark hover:border-crimson',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="icon"
                                    value={icon.value}
                                    checked={data.icon === icon.value}
                                    onChange={() => setData('icon', icon.value)}
                                    className="sr-only"
                                />
                                <Icon name={icon.value} size={22} />
                                <span className="sr-only">{icon.label}</span>
                            </label>
                        ))}
                    </div>
                    <InputError message={errors.icon} />
                </fieldset>
            </Card>

            <Card aria-labelledby="members-heading">
                <div className="mb-4 flex flex-wrap items-end gap-3">
                    <div className="flex-1">
                        <h2 id="members-heading" className="text-xl font-extrabold">
                            Integrantes
                        </h2>
                        <p className="text-muted">
                            Solo aparece el personal de TI activo. {data.members.length} seleccionados.
                        </p>
                    </div>
                    {staff.length > 8 && (
                        <div className="w-full sm:w-64">
                            <label htmlFor="staff-search" className="sr-only">
                                Buscar persona
                            </label>
                            <TextInput
                                id="staff-search"
                                type="search"
                                value={staffSearch}
                                placeholder="Buscar persona"
                                onChange={(e) => setStaffSearch(e.target.value)}
                            />
                        </div>
                    )}
                </div>

                <ul className="divide-y divide-line">
                    {visibleStaff.map((person) => {
                        const member = membership(person.id);

                        return (
                            <li key={person.id} className="flex flex-wrap items-center gap-3 py-3">
                                <label className="flex flex-1 cursor-pointer items-center gap-3">
                                    <Checkbox
                                        checked={member !== undefined}
                                        onChange={(e) => toggleMember(person.id, e.target.checked)}
                                    />
                                    <Avatar initials={person.initials} size="sm" />
                                    <span className="leading-tight">
                                        <span className="block font-extrabold">{person.name}</span>
                                        <span className="block text-sm text-muted">
                                            {person.job_title ?? person.role?.label}
                                        </span>
                                    </span>
                                </label>

                                {member && (
                                    <label className="flex items-center gap-2 text-sm font-bold">
                                        <Checkbox
                                            checked={member.is_lead}
                                            onChange={(e) => toggleLead(person.id, e.target.checked)}
                                        />
                                        Líder del equipo
                                        <span className="sr-only">: {person.name}</span>
                                    </label>
                                )}
                            </li>
                        );
                    })}
                </ul>

                {visibleStaff.length === 0 && (
                    <p className="py-4 text-muted">No hay personal de TI que coincida con la búsqueda.</p>
                )}

                {memberErrors.map((message) => (
                    <InputError key={message} message={message} />
                ))}
            </Card>

            <div className="flex flex-wrap justify-end gap-3">
                <ButtonLink href={route('management.teams.index')} variant="secondary">
                    Cancelar
                </ButtonLink>
                <PrimaryButton disabled={processing}>
                    {isEditing ? 'Guardar cambios' : 'Crear equipo'}
                </PrimaryButton>
            </div>
        </form>
    );
}
