import { IconName } from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { Category, DescribedOption, Option, SlaPolicy, Team, TicketTypeValue } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import CategoryForm, { CategoryFormData } from './Partials/CategoryForm';

export default function Edit({
    category,
    teams,
    policies,
    icons,
    ticketTypes,
}: {
    category: Category;
    teams: Team[];
    policies: SlaPolicy[];
    icons: Option<IconName>[];
    ticketTypes: DescribedOption<TicketTypeValue>[];
}) {
    const form = useForm<CategoryFormData>({
        team_id: category.team_id,
        sla_policy_id: category.sla_policy_id ?? '',
        name: category.name,
        description: category.description ?? '',
        icon: category.icon,
        default_ticket_type: category.default_ticket_type.value,
        is_visible: category.is_visible,
        sort_order: category.sort_order,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.put(route('management.categories.update', category.id), formSubmitOptions);
    };

    return (
        <AppLayout title={`Editar ${category.name}`}>
            <PageHeader title={category.name} description="Categoría de atención" />
            <CategoryForm
                form={form}
                teams={teams}
                policies={policies}
                icons={icons}
                ticketTypes={ticketTypes}
                currentTeamId={category.team_id}
                isEditing
                onSubmit={submit}
            />
        </AppLayout>
    );
}
