import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { BusinessCalendar } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import BusinessCalendarForm, {
    BusinessCalendarFormData,
    toWorkdays,
    WeekdayOption,
} from './Partials/BusinessCalendarForm';

export default function Edit({
    calendar,
    weekdays,
    timezones,
}: {
    calendar: BusinessCalendar;
    weekdays: WeekdayOption[];
    timezones: string[];
}) {
    const form = useForm<BusinessCalendarFormData>({
        name: calendar.name,
        timezone: calendar.timezone,
        is_default: calendar.is_default,
        hours: toWorkdays(weekdays, calendar.hours ?? []),
        holidays: (calendar.holidays ?? []).map((holiday) => ({
            name: holiday.name,
            date: holiday.date,
        })),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.put(route('management.service-levels.calendars.update', calendar.id), formSubmitOptions);
    };

    return (
        <AppLayout title={`Editar ${calendar.name}`}>
            <PageHeader title={calendar.name} description="Horario laboral" />
            <BusinessCalendarForm
                form={form}
                weekdays={weekdays}
                timezones={timezones}
                isCurrentDefault={calendar.is_default}
                isEditing
                onSubmit={submit}
            />
        </AppLayout>
    );
}
