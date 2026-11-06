import Badge from '@/Components/ui/Badge';
import ButtonLink from '@/Components/ui/ButtonLink';
import Card from '@/Components/ui/Card';
import Checkbox from '@/Components/ui/Checkbox';
import FormField from '@/Components/ui/FormField';
import Icon from '@/Components/ui/Icon';
import InputError from '@/Components/ui/InputError';
import PrimaryButton from '@/Components/ui/PrimaryButton';
import SecondaryButton from '@/Components/ui/SecondaryButton';
import Select from '@/Components/ui/Select';
import TextInput from '@/Components/ui/TextInput';
import { BusinessCalendar } from '@/types';
import { InertiaFormProps } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export interface WorkdayFormData {
    weekday: number;
    is_working: boolean;
    opens_at: string;
    closes_at: string;
}

export interface HolidayFormData {
    name: string;
    date: string;
}

export interface BusinessCalendarFormData {
    name: string;
    timezone: string;
    is_default: boolean;
    hours: WorkdayFormData[];
    holidays: HolidayFormData[];
}

export interface WeekdayOption {
    value: number;
    label: string;
}

/**
 * Turns the stored working intervals into one row per weekday, as the form shows them.
 */
export function toWorkdays(
    weekdays: WeekdayOption[],
    hours: NonNullable<BusinessCalendar['hours']>,
): WorkdayFormData[] {
    return weekdays.map((weekday) => {
        const interval = hours.find((hour) => hour.weekday === weekday.value);

        return {
            weekday: weekday.value,
            is_working: interval !== undefined,
            opens_at: interval?.opens_at ?? '07:00',
            closes_at: interval?.closes_at ?? '18:00',
        };
    });
}

export default function BusinessCalendarForm({
    form,
    weekdays,
    timezones,
    isCurrentDefault,
    isEditing,
    onSubmit,
}: {
    form: InertiaFormProps<BusinessCalendarFormData>;
    weekdays: WeekdayOption[];
    timezones: string[];
    isCurrentDefault: boolean;
    isEditing: boolean;
    onSubmit: FormEventHandler;
}) {
    const { data, setData, errors, processing } = form;
    const fieldErrors = errors as Record<string, string | undefined>;

    const updateWorkday = (index: number, changes: Partial<WorkdayFormData>) =>
        setData(
            'hours',
            data.hours.map((day, position) => (position === index ? { ...day, ...changes } : day)),
        );

    const updateHoliday = (index: number, changes: Partial<HolidayFormData>) =>
        setData(
            'holidays',
            data.holidays.map((holiday, position) => (position === index ? { ...holiday, ...changes } : holiday)),
        );

    const labelOf = (weekday: number) => weekdays.find((option) => option.value === weekday)?.label ?? '';

    return (
        <form onSubmit={onSubmit} noValidate className="flex max-w-4xl flex-col gap-5">
            <Card aria-labelledby="calendar-heading">
                <h2 id="calendar-heading" className="mb-4 text-xl font-extrabold">
                    Información del horario
                </h2>

                <div className="grid gap-4 md:grid-cols-2">
                    <FormField id="name" label="Nombre" error={errors.name}>
                        <TextInput
                            id="name"
                            value={data.name}
                            placeholder="Horario de oficina"
                            aria-invalid={errors.name ? true : undefined}
                            onChange={(e) => setData('name', e.target.value)}
                            isFocused={!isEditing}
                        />
                    </FormField>

                    <FormField id="timezone" label="Zona horaria" error={errors.timezone}>
                        <Select id="timezone" value={data.timezone} onChange={(e) => setData('timezone', e.target.value)}>
                            {timezones.map((timezone) => (
                                <option key={timezone} value={timezone}>
                                    {timezone}
                                </option>
                            ))}
                        </Select>
                    </FormField>
                </div>

                <div className="mt-4">
                    {isCurrentDefault ? (
                        <p className="flex flex-wrap items-center gap-2 text-sm font-semibold text-muted">
                            <Badge tone="brand" icon="star">
                                Predeterminado
                            </Badge>
                            Para cambiarlo, marca otro horario como predeterminado.
                        </p>
                    ) : (
                        <label className="flex min-h-12 items-center gap-3 font-bold">
                            <Checkbox
                                checked={data.is_default}
                                onChange={(e) => setData('is_default', e.target.checked)}
                            />
                            Usar como horario predeterminado
                        </label>
                    )}
                    <InputError message={errors.is_default} />
                </div>
            </Card>

            <Card aria-labelledby="hours-heading">
                <h2 id="hours-heading" className="text-xl font-extrabold">
                    Días y horas laborales
                </h2>
                <p className="mb-4 text-muted">Fuera de estas horas el reloj de los SLA se detiene.</p>

                <ul className="divide-y divide-line">
                    {data.hours.map((day, index) => {
                        const label = labelOf(day.weekday);
                        const opensError = fieldErrors[`hours.${index}.opens_at`];
                        const closesError = fieldErrors[`hours.${index}.closes_at`];

                        return (
                            <li key={day.weekday} className="flex flex-wrap items-center gap-x-4 gap-y-2 py-3">
                                <label className="flex w-40 items-center gap-3 font-extrabold">
                                    <Checkbox
                                        checked={day.is_working}
                                        onChange={(e) => updateWorkday(index, { is_working: e.target.checked })}
                                    />
                                    {label}
                                </label>

                                {day.is_working ? (
                                    <div className="flex flex-wrap items-center gap-2">
                                        <TextInput
                                            type="time"
                                            value={day.opens_at}
                                            aria-label={`${label}, hora de apertura`}
                                            aria-invalid={opensError ? true : undefined}
                                            className="w-36"
                                            onChange={(e) => updateWorkday(index, { opens_at: e.target.value })}
                                        />
                                        <span className="font-bold text-muted">a</span>
                                        <TextInput
                                            type="time"
                                            value={day.closes_at}
                                            aria-label={`${label}, hora de cierre`}
                                            aria-invalid={closesError ? true : undefined}
                                            className="w-36"
                                            onChange={(e) => updateWorkday(index, { closes_at: e.target.value })}
                                        />
                                    </div>
                                ) : (
                                    <span className="font-semibold text-muted">No laborable</span>
                                )}

                                {(opensError || closesError) && (
                                    <div className="basis-full">
                                        <InputError message={opensError ?? closesError} />
                                    </div>
                                )}
                            </li>
                        );
                    })}
                </ul>
                <InputError message={errors.hours} />
            </Card>

            <Card aria-labelledby="holidays-heading">
                <div className="mb-4 flex flex-wrap items-end gap-3">
                    <div className="flex-1">
                        <h2 id="holidays-heading" className="text-xl font-extrabold">
                            Festivos
                        </h2>
                        <p className="text-muted">Días en los que el reloj de los SLA no corre.</p>
                    </div>
                    <SecondaryButton
                        size="sm"
                        onClick={() => setData('holidays', [...data.holidays, { name: '', date: '' }])}
                    >
                        <Icon name="plus" size={18} />
                        Agregar festivo
                    </SecondaryButton>
                </div>

                {data.holidays.length === 0 && (
                    <p className="py-2 text-muted">Este horario todavía no tiene festivos.</p>
                )}

                <ul className="flex flex-col gap-3">
                    {data.holidays.map((holiday, index) => {
                        const dateError = fieldErrors[`holidays.${index}.date`];
                        const nameError = fieldErrors[`holidays.${index}.name`];
                        const description = holiday.name || `festivo ${index + 1}`;

                        return (
                            <li key={index} className="flex flex-wrap items-start gap-3">
                                <div className="w-44">
                                    <TextInput
                                        type="date"
                                        value={holiday.date}
                                        aria-label={`Fecha del ${description}`}
                                        aria-invalid={dateError ? true : undefined}
                                        onChange={(e) => updateHoliday(index, { date: e.target.value })}
                                    />
                                    <InputError message={dateError} />
                                </div>
                                <div className="min-w-48 flex-1">
                                    <TextInput
                                        value={holiday.name}
                                        placeholder="Navidad"
                                        aria-label={`Nombre del festivo ${index + 1}`}
                                        aria-invalid={nameError ? true : undefined}
                                        onChange={(e) => updateHoliday(index, { name: e.target.value })}
                                    />
                                    <InputError message={nameError} />
                                </div>
                                <SecondaryButton
                                    className="min-h-12"
                                    onClick={() =>
                                        setData(
                                            'holidays',
                                            data.holidays.filter((_, position) => position !== index),
                                        )
                                    }
                                >
                                    <Icon name="x" size={18} />
                                    <span className="sr-only">Quitar {description}</span>
                                </SecondaryButton>
                            </li>
                        );
                    })}
                </ul>
            </Card>

            <div className="flex flex-wrap justify-end gap-3">
                <ButtonLink href={route('management.service-levels.index')} variant="secondary">
                    Cancelar
                </ButtonLink>
                <PrimaryButton disabled={processing}>
                    {isEditing ? 'Guardar cambios' : 'Crear horario'}
                </PrimaryButton>
            </div>
        </form>
    );
}
