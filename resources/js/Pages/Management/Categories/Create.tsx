import { IconName } from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { DescribedOption, Option, SlaPolicy, Team, TicketTypeValue } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import CategoryForm, { CategoryFormData } from './Partials/CategoryForm';

export default function Create({
    preselectedTeamId,
    teams,
    policies,
    icons,
    ticketTypes,
}: {
    preselectedTeamId: number | null;
    teams: Team[];
    policies: SlaPolicy[];
    icons: Option<IconName>[];
    ticketTypes: DescribedOption<TicketTypeValue>[];
}) {
    const form = useForm<CategoryFormData>({
        team_id: preselectedTeamId ?? '',
        sla_policy_id: '',
        name: '',
        description: '',
        icon: 'help',
        default_ticket_type: 'incident',
        is_visible: true,
        sort_order: 0,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('management.categories.store'), formSubmitOptions);
    };

    return (
        <AppLayout title="Nueva categoría">
            <PageHeader
                title="Nueva categoría"
                description="Define qué pueden solicitar las personas y a qué equipo llega cada solicitud."
            />
            <CategoryForm
                form={form}
                teams={teams}
                policies={policies}
                icons={icons}
                ticketTypes={ticketTypes}
                currentTeamId={null}
                isEditing={false}
                onSubmit={submit}
            />
        </AppLayout>
    );
}
