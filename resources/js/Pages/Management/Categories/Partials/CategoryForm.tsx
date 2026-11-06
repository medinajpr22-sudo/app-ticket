import PriorityBadge from '@/Components/Ticketing/PriorityBadge';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import Checkbox from '@/Components/ui/Checkbox';
import FormField from '@/Components/ui/FormField';
import { IconName } from '@/Components/ui/Icon';
import IconPicker from '@/Components/ui/IconPicker';
import InputError from '@/Components/ui/InputError';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import RadioCards from '@/Components/ui/RadioCards';
import Select from '@/Components/ui/Select';
import TextInput from '@/Components/ui/TextInput';
import { formatDuration } from '@/lib/formatDuration';
import { DescribedOption, Option, SlaPolicy, Team, TicketTypeValue } from '@/types';
import { InertiaFormProps } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export interface CategoryFormData {
    team_id: number | '';
    sla_policy_id: number | '';
    name: string;
    description: string;
    icon: IconName;
    default_ticket_type: TicketTypeValue;
    is_visible: boolean;
    sort_order: number;
}

export default function CategoryForm({
    form,
    teams,
    policies,
    icons,
    ticketTypes,
    currentTeamId,
    isEditing,
    onSubmit,
}: {
    form: InertiaFormProps<CategoryFormData>;
    teams: Team[];
    policies: SlaPolicy[];
    icons: Option<IconName>[];
    ticketTypes: DescribedOption<TicketTypeValue>[];
    currentTeamId: number | null;
    isEditing: boolean;
    onSubmit: FormEventHandler;
}) {
    const { data, setData, errors, processing } = form;
    const defaultPolicy = policies.find((policy) => policy.is_default);
    const effectivePolicy = policies.find((policy) => policy.id === data.sla_policy_id) ?? defaultPolicy;

    return (
        <form onSubmit={onSubmit} noValidate className="flex max-w-4xl flex-col gap-5">
            <Card aria-labelledby="category-heading">
                <h2 id="category-heading" className="mb-4 text-xl font-extrabold">
                    Lo que verá la persona
                </h2>

                <div className="grid gap-4 md:grid-cols-2">
                    <FormField id="name" label="Nombre" error={errors.name}>
                        <TextInput
                            id="name"
                            value={data.name}
                            placeholder="Red, WiFi y VPN"
                            aria-invalid={errors.name ? true : undefined}
                            onChange={(e) => setData('name', e.target.value)}
                            isFocused={!isEditing}
                        />
                    </FormField>
                    <FormField id="description" label="Descripción corta" optional error={errors.description}>
                        <TextInput
                            id="description"
                            value={data.description}
                            placeholder="Conectividad e internet"
                            onChange={(e) => setData('description', e.target.value)}
                        />
                    </FormField>
                </div>

                <div className="mt-5">
                    <IconPicker
                        icons={icons}
                        value={data.icon}
                        onChange={(icon) => setData('icon', icon)}
                        error={errors.icon}
                    />
                </div>
            </Card>

            <Card aria-labelledby="routing-heading">
                <h2 id="routing-heading" className="mb-4 text-xl font-extrabold">
                    Quién atiende y en cuánto tiempo
                </h2>

                <div className="grid gap-4 md:grid-cols-2">
                    <FormField
                        id="team_id"
                        label="Equipo responsable"
                        error={errors.team_id}
                        hint="Los tickets de esta categoría llegan a la bandeja de este equipo."
                    >
                        <Select
                            id="team_id"
                            value={data.team_id}
                            aria-describedby="team_id-hint"
                            aria-invalid={errors.team_id ? true : undefined}
                            onChange={(e) => setData('team_id', e.target.value ? Number(e.target.value) : '')}
                        >
                            <option value="">Elige un equipo</option>
                            {teams.map((team) => (
                                <option
                                    key={team.id}
                                    value={team.id}
                                    disabled={!team.is_active && team.id !== currentTeamId}
                                >
                                    {team.name}
                                    {!team.is_active && ' (inactivo)'}
                                </option>
                            ))}
                        </Select>
                    </FormField>

                    <FormField id="sla_policy_id" label="Política de SLA" error={errors.sla_policy_id}>
                        <Select
                            id="sla_policy_id"
                            value={data.sla_policy_id}
                            onChange={(e) =>
                                setData('sla_policy_id', e.target.value ? Number(e.target.value) : '')
                            }
                        >
                            <option value="">Predeterminada ({defaultPolicy?.name})</option>
                            {policies
                                .filter((policy) => !policy.is_default)
                                .map((policy) => (
                                    <option key={policy.id} value={policy.id}>
                                        {policy.name}
                                    </option>
                                ))}
                        </Select>
                    </FormField>
                </div>

                {effectivePolicy?.targets && (
                    <div className="mt-4 rounded-2xl border border-line bg-white/60 p-4">
                        <p className="mb-2 text-sm font-extrabold">
                            Tiempos de «{effectivePolicy.name}» en horas laborales
                        </p>
                        <ul className="grid gap-2 sm:grid-cols-2">
                            {effectivePolicy.targets.map((target) => (
                                <li key={target.priority} className="flex flex-wrap items-center gap-2 text-sm">
                                    <PriorityBadge priority={target.priority} label={target.label} />
                                    <span className="font-semibold text-muted">
                                        responde en {formatDuration(target.first_response_minutes)}, resuelve en{' '}
                                        {formatDuration(target.resolution_minutes)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                <div className="mt-5">
                    <RadioCards
                        name="default_ticket_type"
                        legend="Tipo de ticket que genera"
                        options={ticketTypes}
                        value={data.default_ticket_type}
                        onChange={(type) => setData('default_ticket_type', type)}
                        error={errors.default_ticket_type}
                    />
                </div>
            </Card>

            <Card aria-labelledby="visibility-heading">
                <h2 id="visibility-heading" className="mb-4 text-xl font-extrabold">
                    Visibilidad
                </h2>
                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <label className="flex min-h-12 items-center gap-3 font-bold">
                            <Checkbox
                                checked={data.is_visible}
                                aria-describedby="is_visible-hint"
                                onChange={(e) => setData('is_visible', e.target.checked)}
                            />
                            Visible para las personas
                        </label>
                        <p id="is_visible-hint" className="text-sm font-semibold text-muted">
                            Si la ocultas, nadie podrá elegirla al crear un ticket.
                        </p>
                        <InputError message={errors.is_visible} />
                    </div>

                    <FormField
                        id="sort_order"
                        label="Orden"
                        error={errors.sort_order}
                        hint="Dentro de su equipo, las categorías se muestran de menor a mayor."
                    >
                        <TextInput
                            id="sort_order"
                            type="number"
                            min={0}
                            max={999}
                            value={data.sort_order}
                            aria-describedby="sort_order-hint"
                            className="w-32"
                            onChange={(e) => setData('sort_order', Number.parseInt(e.target.value, 10) || 0)}
                        />
                    </FormField>
                </div>
            </Card>

            <div className="flex flex-wrap justify-end gap-3">
                <ButtonLink href={route('management.categories.index')} variant="secondary">
                    Cancelar
                </ButtonLink>
                <PrimaryButton disabled={processing}>
                    {isEditing ? 'Guardar cambios' : 'Crear categoría'}
                </PrimaryButton>
            </div>
        </form>
    );
}
