import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import BusinessCalendarForm, {
    BusinessCalendarFormData,
    WeekdayOption,
} from './Partials/BusinessCalendarForm';

const SATURDAY = 6;

export default function Create({
    weekdays,
    timezones,
}: {
    weekdays: WeekdayOption[];
    timezones: string[];
}) {
    const form = useForm<BusinessCalendarFormData>({
        name: '',
        timezone: 'America/Bogota',
        is_default: false,
        hours: weekdays.map((weekday) => ({
            weekday: weekday.value,
            is_working: weekday.value < SATURDAY,
            opens_at: '07:00',
            closes_at: '18:00',
        })),
        holidays: [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('management.service-levels.calendars.store'), formSubmitOptions);
    };

    return (
        <AppLayout title="Nuevo horario laboral">
            <PageHeader
                title="Nuevo horario laboral"
                description="Por ejemplo, un horario extendido para los equipos que atienden fines de semana."
            />
            <BusinessCalendarForm
                form={form}
                weekdays={weekdays}
                timezones={timezones}
                isCurrentDefault={false}
                isEditing={false}
                onSubmit={submit}
            />
        </AppLayout>
    );
}
