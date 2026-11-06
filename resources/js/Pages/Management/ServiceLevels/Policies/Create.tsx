import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { BusinessCalendar, Option, SlaPolicy, TicketPriorityValue } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import SlaPolicyForm, { SlaPolicyFormData } from './Partials/SlaPolicyForm';

export default function Create({
    defaultPolicy,
    calendars,
    priorities,
}: {
    defaultPolicy: SlaPolicy | null;
    calendars: BusinessCalendar[];
    priorities: Option<TicketPriorityValue>[];
}) {
    const form = useForm<SlaPolicyFormData>({
        business_calendar_id: calendars.find((calendar) => calendar.is_default)?.id ?? calendars[0]?.id ?? '',
        name: '',
        description: '',
        is_default: false,
        targets: priorities.map((priority) => {
            const template = defaultPolicy?.targets?.find((target) => target.priority === priority.value);

            return {
                priority: priority.value,
                first_response_minutes: template?.first_response_minutes ?? 60,
                resolution_minutes: template?.resolution_minutes ?? 480,
            };
        }),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('management.service-levels.policies.store'), formSubmitOptions);
    };

    return (
        <AppLayout title="Nueva política de SLA">
            <PageHeader
                title="Nueva política de SLA"
                description="Los tiempos parten de la política predeterminada. Ajústalos a lo que necesitas."
            />
            <SlaPolicyForm
                form={form}
                calendars={calendars}
                priorities={priorities}
                isCurrentDefault={false}
                isEditing={false}
                onSubmit={submit}
            />
        </AppLayout>
    );
}
