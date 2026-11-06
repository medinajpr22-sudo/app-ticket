import PriorityBadge from '@/Components/Ticketing/PriorityBadge';
import Badge from '@/Components/ui/Badge';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import Checkbox from '@/Components/ui/Checkbox';
import DurationInput from '@/Components/ui/DurationInput';
import FormField from '@/Components/ui/FormField';
import InputError from '@/Components/ui/InputError';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import Select from '@/Components/ui/Select';
import TextArea from '@/Components/ui/TextArea';
import TextInput from '@/Components/ui/TextInput';
import { BusinessCalendar, Option, TicketPriorityValue } from '@/types';
import { InertiaFormProps } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export interface SlaTargetFormData {
    priority: TicketPriorityValue;
    first_response_minutes: number;
    resolution_minutes: number;
}

export interface SlaPolicyFormData {
    business_calendar_id: number | '';
    name: string;
    description: string;
    is_default: boolean;
    targets: SlaTargetFormData[];
}

export default function SlaPolicyForm({
    form,
    calendars,
    priorities,
    isCurrentDefault,
    isEditing,
    onSubmit,
}: {
    form: InertiaFormProps<SlaPolicyFormData>;
    calendars: BusinessCalendar[];
    priorities: Option<TicketPriorityValue>[];
    isCurrentDefault: boolean;
    isEditing: boolean;
    onSubmit: FormEventHandler;
}) {
    const { data, setData, errors, processing } = form;
    const fieldErrors = errors as Record<string, string | undefined>;

    const updateTarget = (index: number, changes: Partial<SlaTargetFormData>) =>
        setData(
            'targets',
            data.targets.map((target, position) => (position === index ? { ...target, ...changes } : target)),
        );

    const labelOf = (priority: TicketPriorityValue) =>
        priorities.find((option) => option.value === priority)?.label ?? priority;

    return (
        <form onSubmit={onSubmit} noValidate className="flex max-w-4xl flex-col gap-5">
            <Card aria-labelledby="policy-heading">
                <h2 id="policy-heading" className="mb-4 text-xl font-extrabold">
                    Información de la política
                </h2>

                <div className="grid gap-4 md:grid-cols-2">
                    <FormField id="name" label="Nombre" error={errors.name}>
                        <TextInput
                            id="name"
                            value={data.name}
                            placeholder="Sistemas clínicos"
                            aria-invalid={errors.name ? true : undefined}
                            onChange={(e) => setData('name', e.target.value)}
                            isFocused={!isEditing}
                        />
                    </FormField>

                    <FormField
                        id="business_calendar_id"
                        label="Horario laboral"
                        error={errors.business_calendar_id}
                        hint="El tiempo del SLA solo corre dentro de este horario."
                    >
                        <Select
                            id="business_calendar_id"
                            value={data.business_calendar_id}
                            aria-describedby="business_calendar_id-hint"
                            onChange={(e) =>
                                setData('business_calendar_id', e.target.value ? Number(e.target.value) : '')
                            }
                        >
                            {calendars.map((calendar) => (
                                <option key={calendar.id} value={calendar.id}>
                                    {calendar.name}
                                </option>
                            ))}
                        </Select>
                    </FormField>

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
                            placeholder="Incidentes que pueden afectar la atención de pacientes."
                            onChange={(e) => setData('description', e.target.value)}
                        />
                    </FormField>
                </div>

                <div className="mt-4">
                    {isCurrentDefault ? (
                        <p className="flex flex-wrap items-center gap-2 text-sm font-semibold text-muted">
                            <Badge tone="brand" icon="star">
                                Predeterminada
                            </Badge>
                            Para cambiarla, marca otra política como predeterminada.
                        </p>
                    ) : (
                        <>
                            <label className="flex min-h-12 items-center gap-3 font-bold">
                                <Checkbox
                                    checked={data.is_default}
                                    aria-describedby="is_default-hint"
                                    onChange={(e) => setData('is_default', e.target.checked)}
                                />
                                Usar como política predeterminada
                            </label>
                            <p id="is_default-hint" className="text-sm font-semibold text-muted">
                                La usarán todas las categorías que no tengan una política propia.
                            </p>
                        </>
                    )}
                    <InputError message={errors.is_default} />
                </div>
            </Card>

            <Card aria-labelledby="targets-heading">
                <h2 id="targets-heading" className="text-xl font-extrabold">
                    Tiempos por prioridad
                </h2>
                <p className="mb-4 text-muted">
                    En horas laborales. La resolución no puede tomar menos tiempo que la primera respuesta.
                </p>

                <div className="overflow-x-auto">
                    <table className="w-full text-left">
                        <caption className="sr-only">Tiempos de primera respuesta y resolución por prioridad</caption>
                        <thead>
                            <tr className="border-b border-line text-xs tracking-[0.07em] text-muted uppercase">
                                <th scope="col" className="py-3 pe-3">Prioridad</th>
                                <th scope="col" className="px-3 py-3">Primera respuesta</th>
                                <th scope="col" className="py-3 ps-3">Resolución</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.targets.map((target, index) => {
                                const label = labelOf(target.priority);
                                const firstResponseError = fieldErrors[`targets.${index}.first_response_minutes`];
                                const resolutionError = fieldErrors[`targets.${index}.resolution_minutes`];

                                return (
                                    <tr key={target.priority} className="border-b border-line align-top last:border-0">
                                        <th scope="row" className="py-3 pe-3 font-normal">
                                            <span className="mt-3 inline-block">
                                                <PriorityBadge priority={target.priority} label={label} />
                                            </span>
                                        </th>
                                        <td className="px-3 py-3">
                                            <DurationInput
                                                id={`first-response-${target.priority}`}
                                                label={`Primera respuesta, prioridad ${label}`}
                                                minutes={target.first_response_minutes}
                                                isInvalid={Boolean(firstResponseError)}
                                                onChange={(minutes) => updateTarget(index, { first_response_minutes: minutes })}
                                            />
                                            <InputError message={firstResponseError} />
                                        </td>
                                        <td className="py-3 ps-3">
                                            <DurationInput
                                                id={`resolution-${target.priority}`}
                                                label={`Resolución, prioridad ${label}`}
                                                minutes={target.resolution_minutes}
                                                isInvalid={Boolean(resolutionError)}
                                                onChange={(minutes) => updateTarget(index, { resolution_minutes: minutes })}
                                            />
                                            <InputError message={resolutionError} />
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </Card>

            <div className="flex flex-wrap justify-end gap-3">
                <ButtonLink href={route('management.service-levels.index')} variant="secondary">
                    Cancelar
                </ButtonLink>
                <PrimaryButton disabled={processing}>
                    {isEditing ? 'Guardar cambios' : 'Crear política'}
                </PrimaryButton>
            </div>
        </form>
    );
}
