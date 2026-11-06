import { IconName } from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { DescribedOption, Option, Team, TeamTypeValue, User } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import TeamForm, { TeamFormData } from './Partials/TeamForm';

export default function Edit({
    team,
    types,
    icons,
    staff,
}: {
    team: Team;
    types: DescribedOption<TeamTypeValue>[];
    icons: Option<IconName>[];
    staff: User[];
}) {
    const form = useForm<TeamFormData>({
        name: team.name,
        type: team.type.value,
        icon: team.icon,
        description: team.description ?? '',
        is_active: team.is_active,
        members: (team.members ?? []).map((member) => ({
            user_id: member.id,
            is_lead: member.is_lead,
        })),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.put(route('management.teams.update', team.id), formSubmitOptions);
    };

    return (
        <AppLayout title={`Editar ${team.name}`}>
            <PageHeader title={team.name} description={team.type.label} />
            <TeamForm
                form={form}
                types={types}
                icons={icons}
                staff={staff}
                isEditing
                onSubmit={submit}
            />
        </AppLayout>
    );
}
