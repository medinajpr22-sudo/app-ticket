import { IconName } from '@/Components/ui/Icon';
import PageHeader from '@/Components/ui/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import { formSubmitOptions } from '@/lib/formSubmitOptions';
import { DescribedOption, Option, TeamTypeValue, User } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import TeamForm, { TeamFormData } from './Partials/TeamForm';

export default function Create({
    types,
    icons,
    staff,
}: {
    types: DescribedOption<TeamTypeValue>[];
    icons: Option<IconName>[];
    staff: User[];
}) {
    const form = useForm<TeamFormData>({
        name: '',
        type: 'general_support',
        icon: 'users',
        description: '',
        is_active: true,
        members: [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('management.teams.store'), formSubmitOptions);
    };

    return (
        <AppLayout title="Nuevo equipo">
            <PageHeader
                title="Nuevo equipo"
                description="Agrupa al personal de TI que atenderá un mismo tipo de solicitudes."
            />
            <TeamForm
                form={form}
                types={types}
                icons={icons}
                staff={staff}
                isEditing={false}
                onSubmit={submit}
            />
        </AppLayout>
    );
}
