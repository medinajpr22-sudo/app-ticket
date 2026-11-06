import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { BusinessCalendar, Option, SlaPolicy, TicketPriorityValue } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import SlaPolicyForm, { SlaPolicyFormData } from './Partials/SlaPolicyForm';

export default function Edit({
    policy,
    calendars,
    priorities,
}: {
    policy: SlaPolicy;
    calendars: BusinessCalendar[];
    priorities: Option<TicketPriorityValue>[];
}) {
    const form = useForm<SlaPolicyFormData>({
        business_calendar_id: policy.business_calendar_id,
        name: policy.name,
        description: policy.description ?? '',
        is_default: policy.is_default,
        targets: priorities.map((priority) => {
            const target = policy.targets?.find((candidate) => candidate.priority === priority.value);

            return {
                priority: priority.value,
                first_response_minutes: target?.first_response_minutes ?? 60,
                resolution_minutes: target?.resolution_minutes ?? 480,
            };
        }),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.put(route('management.service-levels.policies.update', policy.id), formSubmitOptions);
    };

    return (
        <AppLayout title={`Editar ${policy.name}`}>
            <PageHeader title={policy.name} description="Política de SLA" />
            <SlaPolicyForm
                form={form}
                calendars={calendars}
                priorities={priorities}
                isCurrentDefault={policy.is_default}
                isEditing
                onSubmit={submit}
            />
        </AppLayout>
    );
}
